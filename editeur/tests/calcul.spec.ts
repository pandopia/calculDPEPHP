import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { Dossier } from '../src/app/core/state/dossier';
import { integrerResultats } from '../src/app/core/calcul/integrer-resultats';
import { MoteurPandopia } from '../src/app/core/calcul/moteur-calcul';
import { buildModel } from '../src/app/core/metier/model';
import { indexedPath, parseXml } from '../src/app/core/xml/safe-xml';
import { semanticDiff } from '../src/app/core/xml/semantic-compare';
import { FIXTURES_DIR, fixture, nodeSchemas } from './helpers';

// réponse réelle du moteur Pandopia pour ce DPE, enregistrée (pas d'appel réseau en test)
const CALCULE = readFileSync(join(FIXTURES_DIR, '2600E0083091A.calcule.xml'), 'utf8');

async function dossier(): Promise<Dossier> {
  return Dossier.fromImport(fixture('2600E0083091A.xml'), '2600E0083091A.xml', nodeSchemas());
}

describe('intégration du calcul', () => {
  it('remplace uniquement les résultats et lève l\'obsolescence', async () => {
    const d = await dossier();
    const envoye = d.exportXml();
    // échange réel enregistré : ce qui a été envoyé et la réponse du moteur
    expect(semanticDiff(parseXml(envoye), parseXml(fixture('2600E0083091A.envoye.xml')))).toEqual([]);
    const coutAvant = d.working.getElementsByTagName('cout_5_usages')[0].textContent;
    d.meta.resultatsObsoletes = true;
    const r = integrerResultats(d, envoye, CALCULE, 'Moteur de test');
    expect(r.remplaces).toBeGreaterThan(10);
    expect(d.meta.resultatsObsoletes).toBe(false);
    expect(d.meta.calcul?.moteur).toBe('Moteur de test');
    // même contenu que la réponse du moteur, bloc de résultats par bloc ; l'ordre
    // des blocs du fichier est conservé (xs:all, ordre libre)
    const out = parseXml(d.exportXml());
    const calc = parseXml(CALCULE);
    const blocks = (doc: Document) => new Map(['donnee_intermediaire', 'sortie'].flatMap((t) => Array.from(doc.getElementsByTagName(t)))
      .map((e) => [indexedPath(e.parentElement!) + '/' + e.localName, e] as const));
    const bo = blocks(out);
    const bc = blocks(calc);
    expect([...bo.keys()].sort()).toEqual([...bc.keys()].sort());
    for (const [k, e] of bc) {
      const a = parseXml(new XMLSerializer().serializeToString(bo.get(k)!));
      expect(semanticDiff(a, parseXml(new XMLSerializer().serializeToString(e))), k).toEqual([]);
    }

    expect(buildModel(d).issues.filter((i) => i.gravite === 'erreur')).toEqual([]);
    d.undo();
    expect(d.working.getElementsByTagName('cout_5_usages')[0].textContent).toBe(coutAvant);
  });

  it('refuse une réponse dont les données d\'entrée diffèrent de l\'envoi', async () => {
    const d = await dossier();
    const envoye = d.exportXml();
    const altere = CALCULE.replace(/<hsp>[^<]*<\/hsp>/, '<hsp>9.9</hsp>');
    expect(() => integrerResultats(d, envoye, altere, 'Moteur')).toThrow(/données d'entrée différentes/);
    expect(d.canUndo()).toBe(false);
  });

  it('refuse une réponse qui n\'est pas un XML DPE', async () => {
    const d = await dossier();
    expect(() => integrerResultats(d, d.exportXml(), '<html><body>Erreur</body></html>', 'M')).toThrow(/pas un XML DPE/);
    expect(() => integrerResultats(d, d.exportXml(), '<dpe><a></dpe>', 'M')).toThrow(/illisible/);
  });

  it('moteur Pandopia : POST du XML et erreurs HTTP explicites', async () => {
    const calls: { url: string; init: RequestInit }[] = [];
    const ok = new MoteurPandopia('https://exemple.test/calc', 1000, (async (url: string, init: RequestInit) => {
      calls.push({ url, init });
      return new Response('<dpe/>', { status: 200 });
    }) as unknown as typeof fetch);
    expect(await ok.calculer('<dpe/>')).toBe('<dpe/>');
    expect(calls[0].init.method).toBe('POST');
    expect((calls[0].init.headers as Record<string, string>)['Content-Type']).toBe('application/xml');
    const ko = new MoteurPandopia('https://exemple.test/calc', 1000, (async () => new Response('<p>XML invalide</p>', { status: 422 })) as unknown as typeof fetch);
    await expect(ko.calculer('<dpe/>')).rejects.toThrow(/HTTP 422.*XML invalide/);
    const down = new MoteurPandopia('https://exemple.test/calc', 1000, (async () => { throw new TypeError('network'); }) as unknown as typeof fetch);
    await expect(down.calculer('<dpe/>')).rejects.toThrow(/injoignable/);
  });
});
