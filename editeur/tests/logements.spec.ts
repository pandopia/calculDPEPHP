import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { Dossier } from '../src/app/core/state/dossier';
import { ServiceLogementsPandopia } from '../src/app/core/calcul/moteur-calcul';
import {
  ConfigImmeuble, configVide, contexteImmeuble, controler, empreinte, lireReponse, liaisonsUtiles, logementsManquants,
  parois, preRemplir, requete, resultats, synthese,
} from '../src/app/core/immeuble/logements';
import { fixture, nodeSchemas } from './helpers';

// DPE immeuble réel (chauffage et ECS collectifs, 48 appartements) du moteur PHP
const IMMEUBLE = readFileSync(join(__dirname, '..', '..', 'tests', 'Fixtures', 'comparatif-collectif.xml'), 'utf8');

let seq = 0;
const id = () => 'l' + seq++;

async function dossier(): Promise<Dossier> {
  return Dossier.fromImport(IMMEUBLE, 'immeuble.xml', nodeSchemas());
}

function complet(d: Dossier): ConfigImmeuble {
  const cfg = configVide();
  cfg.logements.push(...logementsManquants(cfg, contexteImmeuble(d.working), id));
  return cfg;
}

describe('DPE des logements générés depuis l\'immeuble (§17.2.2)', () => {
  it('lit le contexte de l\'immeuble', async () => {
    const d = await dossier();
    const c = contexteImmeuble(d.working);
    expect(c).toMatchObject({ methode: '9', estImmeuble: true, chauffage: 'collectif', ecs: 'collectif', nombreAppartements: 48, nonSupporte: null });
    expect(c.surfaceImmeuble).toBeCloseTo(3780.58, 2);
    const maison = await Dossier.fromImport(fixture('2600E0083091A.xml'), 'm.xml', nodeSchemas());
    expect(contexteImmeuble(maison.working).nonSupporte).toMatch(/pas un DPE immeuble/);
  });

  it('crée les logements manquants avec une somme de surfaces exacte', async () => {
    const d = await dossier();
    const cfg = complet(d);
    expect(cfg.logements).toHaveLength(48);
    expect(new Set(cfg.logements.map((l) => l.reference)).size).toBe(48);
    const somme = cfg.logements.reduce((s, l) => s + l.surface!, 0);
    expect(Math.abs(somme - 3780.58)).toBeLessThan(0.005);
    expect(logementsManquants(cfg, contexteImmeuble(d.working), id)).toEqual([]);
  });

  it('bloque tant que la collection est incomplète ou la répartition indéterminée', async () => {
    const d = await dossier();
    const ctx = contexteImmeuble(d.working);
    const liste = parois(d.working, d.schema);
    expect(controler(configVide(), ctx, liste)[0].message).toMatch(/Aucun logement/);
    const cfg = complet(d);
    cfg.logements.pop();
    const msgs = controler(cfg, ctx, liste).filter((c) => c.gravite === 'erreur').map((c) => c.message);
    expect(msgs.some((m) => /47 logement\(s\) décrit\(s\) pour 48/.test(m))).toBe(true);
    expect(msgs.some((m) => /Somme des surfaces/.test(m))).toBe(true);
    expect(msgs.some((m) => /frais de chauffage sont individualisés/.test(m))).toBe(true);
  });

  it('sans individualisation des frais, répartit à la surface sans exiger de liaisons', async () => {
    const d = await dossier();
    const ctx = contexteImmeuble(d.working);
    const cfg = complet(d);
    cfg.repartitionChauffage = 2;
    expect(liaisonsUtiles(cfg, ctx)).toBe(false);
    expect(controler(cfg, ctx, parois(d.working, d.schema)).filter((c) => c.gravite === 'erreur')).toEqual([]);
    const req = requete(cfg, ctx, '<dpe/>');
    expect(req.repartition).toEqual({ chauffage: 2, ecs: 1, coefficientIfc: null, approximerLiaisons: false });
    expect(req.logements[0]).toMatchObject({ reference: 'Lot 1', liaisons: { murs: [], plancher: [], plafond: [], fenetre: [], porte: [], pont_thermique: [] } });
  });

  it('avec individualisation, exige que chaque paroi soit reliée, sauf approximation demandée', async () => {
    const d = await dossier();
    const ctx = contexteImmeuble(d.working);
    const liste = parois(d.working, d.schema);
    const cfg = complet(d);
    cfg.repartitionChauffage = 1;
    expect(liaisonsUtiles(cfg, ctx)).toBe(true);
    expect(controler(cfg, ctx, liste).find((c) => c.gravite === 'erreur')?.message).toMatch(/reliée\(s\) à aucun logement/);
    cfg.approximerLiaisons = true;
    expect(controler(cfg, ctx, liste).filter((c) => c.gravite === 'erreur')).toEqual([]);
    cfg.coefIfc = 0;
    expect(liaisonsUtiles(cfg, ctx)).toBe(false);
    cfg.coefIfc = 1.5;
    expect(controler(cfg, ctx, liste).some((c) => /compris entre 0 et 1/.test(c.message))).toBe(true);
  });

  it('n\'exige une liaison de pont thermique que s\'il n\'est pas rattaché à ses parois', async () => {
    const d = await dossier();
    const ctx = contexteImmeuble(d.working);
    const relies = (cfg: ConfigImmeuble, liste: ReturnType<typeof parois>) => {
      for (const l of cfg.logements) for (const p of liste) if (p.reference && p.type !== 'pont_thermique') l.liaisons[p.type].push(p.reference);
    };
    const liste = parois(d.working, d.schema);
    const ponts = liste.filter((p) => p.type === 'pont_thermique');
    expect(ponts).toHaveLength(36);
    expect(ponts.every((p) => p.suitParois)).toBe(true);
    const cfg = complet(d);
    cfg.repartitionChauffage = 1;
    relies(cfg, liste);
    expect(controler(cfg, ctx, liste).filter((c) => c.gravite === 'erreur')).toEqual([]);
    // ponts « manuels » sans référence de paroi (export LICIEL)
    for (const tag of ['reference_1', 'reference_2']) for (const e of Array.from(d.working.getElementsByTagName(tag))) e.remove();
    const manuels = parois(d.working, d.schema);
    expect(manuels.filter((p) => p.type === 'pont_thermique').some((p) => p.suitParois)).toBe(false);
    const msg = controler(cfg, ctx, manuels).find((c) => c.gravite === 'erreur')?.message;
    expect(msg).toMatch(/36 paroi\(s\) reliée\(s\) à aucun logement.*se relie directement aux logements/);
    for (const l of cfg.logements) l.liaisons.pont_thermique = manuels.filter((p) => p.type === 'pont_thermique').map((p) => p.reference!);
    expect(controler(cfg, ctx, manuels).filter((c) => c.gravite === 'erreur')).toEqual([]);
  });

  it('pré-remplit planchers selon l\'étage et baies selon leur paroi support', async () => {
    const d = await dossier();
    const liste = parois(d.working, d.schema);
    const cfg = complet(d);
    cfg.logements[0].position = 1;
    cfg.logements[47].position = 3;
    const baie = liste.find((p) => p.type === 'fenetre' && p.support)!;
    const support = liste.find((p) => p.reference === baie.support)!;
    cfg.logements[5].liaisons[support.type].push(support.reference!);
    expect(preRemplir(cfg, liste)).toBeGreaterThan(0);
    const pb = liste.filter((p) => p.type === 'plancher').map((p) => p.reference);
    const ph = liste.filter((p) => p.type === 'plafond').map((p) => p.reference);
    expect(cfg.logements[0].liaisons.plancher).toEqual(pb);
    expect(cfg.logements[47].liaisons.plafond).toEqual(ph);
    expect(cfg.logements[1].liaisons.plancher).toEqual([]);
    expect(cfg.logements[5].liaisons.fenetre).toContain(baie.reference);
    expect(preRemplir(cfg, liste)).toBe(0);
  });

  it('lit la réponse du service et synthétise chaque logement depuis son XML', () => {
    const calcule = fixture('2600E0083091A.calcule.xml');
    const rep = lireReponse({
      batiment: { erreur: null, xml: calcule },
      logements: [
        { reference: 'A', erreur: null, xml: calcule },
        { reference: 'B', erreur: 'Donnée manquante', xml: null },
        { reference: 'C', erreur: null, xml: null, resultats: { classe_bilan_dpe: 'C', ep_conso_5_usages_m2: 120.5, emission_ges_5_usages_m2: 9 } },
      ],
      hypotheses: ['h1'],
    });
    const r = resultats(rep, 'emp', 'Moteur');
    expect(r.logements[0].synthese?.classeEnergie).toMatch(/^[A-G]$/);
    expect(r.logements[0].synthese?.epM2).toBeGreaterThan(0);
    expect(r.logements[1]).toEqual({ reference: 'B', erreur: 'Donnée manquante', synthese: null });
    expect(r.logements[2].synthese).toMatchObject({ classeEnergie: 'C', epM2: 120.5, gesM2: 9 });
    expect(r.hypotheses).toEqual(['h1']);
    expect(synthese(null, null)).toBeNull();
    expect(() => lireReponse({ error: 'x' })).toThrow(/batiment/);
  });

  it('l\'empreinte ignore les résultats mais suit les entrées et les logements', async () => {
    const d = await dossier();
    const cfg = complet(d);
    const xml = d.exportXml();
    const e = empreinte(xml, cfg);
    expect(empreinte(xml.replace(/<sortie>[\s\S]*?<\/sortie>/, '<sortie><x>1</x></sortie>'), cfg)).toBe(e);
    expect(empreinte(xml.replace(/<hsp>[^<]*<\/hsp>/, '<hsp>9.9</hsp>'), cfg)).not.toBe(e);
    cfg.logements[0].liaisons.murs.push('m');
    expect(empreinte(xml, cfg)).not.toBe(e);
  });

  it('conserve logements et résultats dans le brouillon, jamais dans le XML exporté', async () => {
    const d = await dossier();
    const avant = d.exportXml();
    d.modifierImmeuble('Logements', (cfg) => { cfg.logements.push(...complet(d).logements); cfg.repartitionChauffage = 2; });
    expect(d.immeuble().logements).toHaveLength(48);
    expect(d.meta.resultatsObsoletes).toBe(false);
    expect(d.exportXml()).toBe(avant);
    d.setResultatsLogements({ date: 'x', moteur: 'm', empreinte: 'e', batiment: { erreur: null, synthese: null }, logements: [], hypotheses: [] });
    const repris = await Dossier.fromDraft(JSON.parse(JSON.stringify(d.toDraft())), nodeSchemas());
    expect(repris.immeuble().logements).toHaveLength(48);
    expect(repris.resultatsLogements?.empreinte).toBe('e');
    d.meta.immeuble!.logements[0].liaisons = { murs: ['m'] } as never;
    expect(d.immeuble().logements[0].liaisons.pont_thermique).toEqual([]);
    d.undo();
    expect(d.immeuble().logements).toHaveLength(0);
    expect(d.resultatsLogements?.empreinte).toBe('e');
  });

  it('appelle les deux points d\'API en JSON', async () => {
    const calls: { url: string; body: string }[] = [];
    const ok = new ServiceLogementsPandopia('https://api.test/api/calculdpe', 1000, (async (url: string, init: RequestInit) => {
      calls.push({ url, body: String(init.body) });
      return url.endsWith('pdflogement')
        ? new Response('%PDF-1.7 x', { status: 200, headers: { 'Content-Type': 'application/pdf' } })
        : new Response(JSON.stringify({ batiment: { erreur: null, xml: null }, logements: [] }), { status: 200 });
    }) as unknown as typeof fetch);
    expect(await ok.calculer({ a: 1 })).toEqual({ batiment: { erreur: null, xml: null }, logements: [] });
    expect((await ok.pdf({ logement: 'A' })).size).toBeGreaterThan(5);
    expect(calls.map((c) => c.url)).toEqual(['https://api.test/api/calculdpe/logements', 'https://api.test/api/calculdpe/pdflogement']);
    expect(JSON.parse(calls[1].body)).toEqual({ logement: 'A' });
    const absent = new ServiceLogementsPandopia('https://api.test/x', 1000, (async () => new Response('', { status: 404 })) as unknown as typeof fetch);
    await expect(absent.calculer({})).rejects.toThrow(/pas encore disponible/);
    const refus = new ServiceLogementsPandopia('https://api.test/x', 1000, (async () => new Response('{"error":"Surfaces incohérentes"}', { status: 422 })) as unknown as typeof fetch);
    await expect(refus.calculer({})).rejects.toThrow(/HTTP 422\) : Surfaces incohérentes/);
  });
});
