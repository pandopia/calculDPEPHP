import { describe, expect, it } from 'vitest';
import { Dossier } from '../src/app/core/state/dossier';
import { KIND_BY_KEY } from '../src/app/core/metier/catalog';
import { objectsOfKind, textOf } from '../src/app/core/edition/doc-ops';
import { cleSource, concorde, definirOrigine, indexerSources, LIBELLES, origineApplicable } from '../src/app/core/metier/sources';
import { getUid } from '../src/app/core/xml/uid';
import { fixture, nodeSchemas } from './helpers';

async function dossier(): Promise<Dossier> {
  return Dossier.fromImport(fixture('2600E0083091A.xml'), 'x.xml', nodeSchemas());
}

const kind = (k: string) => KIND_BY_KEY.get(k)!;

describe('origine des données (fiches techniques)', () => {
  it('chaque champ de la table des libellés existe dans le XSD 2.6', async () => {
    const schema = (await nodeSchemas().forVersion('2.6'))!;
    const chemins: Record<string, string> = {
      '1': kind('mur').path, '2': kind('plancher_bas').path, '3': kind('plancher_haut').path, '4': kind('baie_vitree').path,
      '5': kind('porte').path, '6': kind('pont_thermique').path, '7': kind('installation_chauffage').path, '8': kind('installation_ecs').path,
      '9': kind('climatisation').path, '10': kind('ventilation').path,
    };
    const sous: Record<string, Record<string, string>> = {
      '7': { gen: kind('generateur_chauffage').path, em: kind('emetteur_chauffage').path },
      '8': { gen: kind('generateur_ecs').path },
    };
    const general: Record<string, string> = { cg: 'dpe/logement/caracteristique_generale', meteo: 'dpe/logement/meteo' };
    for (const [cat, table] of Object.entries(LIBELLES)) {
      for (const [libelle, champs] of Object.entries(table)) {
        for (const c of champs) {
          const [p, rel] = c.includes(':') ? c.split(':') : ['', c];
          const base = p ? (sous[cat]?.[p] ?? general[p]) : chemins[cat];
          expect(schema.def(`${base}/${rel}`), `${cat} « ${libelle} » → ${c}`).not.toBeNull();
        }
      }
    }
  });

  it('relie chaque fiche à son objet et ses lignes aux champs, valeurs concordantes', async () => {
    const d = await dossier();
    const idx = indexerSources(d.working, d.schema);
    expect(idx.champs.size).toBeGreaterThan(40);
    // tous les murs ont une fiche, et leur surface est documentée par une ligne qui la reprend
    for (const mur of objectsOfKind(d.working, kind('mur'))) {
      const u = getUid(mur)!;
      expect(idx.fiches.has(u)).toBe(true);
      const s = [...idx.champs].find(([k]) => k.startsWith(u + '|') && /surface_paroi/.test(k));
      expect(s, textOf(mur, 'donnee_entree/description') ?? '').toBeTruthy();
      expect(concorde(s![1].valeur, d.index().get(u)!.getElementsByTagName(s![0].split('|')[1].split('/').pop()!)[0], d.schema)).toBe(true);
    }
  });

  it('change l\'origine d\'une ligne existante, ou crée la ligne dans la fiche de l\'objet', async () => {
    const d = await dossier();
    const mur = objectsOfKind(d.working, kind('mur'))[0];
    const u = getUid(mur)!;
    let idx = indexerSources(d.working, d.schema);
    const [cle, src] = [...idx.champs].find(([k]) => k.startsWith(u + '|'))!;
    const rel = cle.split('|')[1];
    definirOrigine(d, idx, u, rel, '3', 'x', null);
    idx = indexerSources(d.working, d.schema);
    expect(idx.champs.get(cle)?.origine).toBe('3');
    expect(idx.champs.get(cle)?.ligneUid).toBe(src.ligneUid);
    d.undo();
    expect(indexerSources(d.working, d.schema).champs.get(cle)?.origine).toBe(src.origine);

    // champ sans ligne : créée dans la fiche du mur, au format « Libellé: valeur », puis retrouvée
    const libre = ['donnee_entree/enum_orientation_id', 'donnee_entree/paroi_lourde', 'donnee_entree/enum_type_doublage_id']
      .find((r) => textOf(mur, r) && !idx.champs.has(cleSource(u, r)))!;
    expect(libre).toBeTruthy();
    expect(origineApplicable(d, u, libre)).toBe(true);
    const fiche = d.index().get(idx.fiches.get(u)!)!;
    const avant = fiche.getElementsByTagName('sous_fiche_technique').length;
    definirOrigine(d, idx, u, libre, '5', libre === 'donnee_entree/paroi_lourde' ? 'Paroi lourde (inertie)' : libre === 'donnee_entree/enum_orientation_id' ? 'Orientation' : 'Doublage intérieur', 'Valeur');
    expect(fiche.getElementsByTagName('sous_fiche_technique').length).toBe(avant + 1);
    const apres = indexerSources(d.working, d.schema).champs.get(cleSource(u, libre));
    expect(apres?.origine).toBe('5');
    expect(apres?.valeur).toBe('Valeur');
    expect(d.exportXml()).toContain('<enum_origine_donnee_id>5</enum_origine_donnee_id>');
    d.undo();
    // l'annulation reconstruit le document de travail : on relit la fiche
    expect(d.index().get(idx.fiches.get(u)!)!.getElementsByTagName('sous_fiche_technique').length).toBe(avant);
  });

  it('ne propose pas d\'origine hors des données saisies d\'un composant', async () => {
    const d = await dossier();
    const mur = getUid(objectsOfKind(d.working, kind('mur'))[0])!;
    expect(origineApplicable(d, mur, 'donnee_entree/reference')).toBe(false);
    expect(origineApplicable(d, mur, 'donnee_intermediaire/umur')).toBe(false);
    const admin = getUid(d.working.getElementsByTagName('administratif')[0])!;
    expect(origineApplicable(d, admin, 'date_visite_diagnostiqueur')).toBe(false);
  });
});
