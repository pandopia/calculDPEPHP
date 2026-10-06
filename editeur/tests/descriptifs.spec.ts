import { describe, expect, it } from 'vitest';
import { Dossier } from '../src/app/core/state/dossier';
import { KIND_BY_KEY } from '../src/app/core/metier/catalog';
import { objectsOfKind, textOf } from '../src/app/core/edition/doc-ops';
import { setFieldValue } from '../src/app/core/edition/editor';
import { genererDescriptifs } from '../src/app/core/metier/descriptifs';
import { buildSkeleton } from '../src/app/core/state/skeleton';
import { getUid } from '../src/app/core/xml/uid';
import { fixture, nodeSchemas } from './helpers';

const descriptifs = (d: Dossier, cat: string) => Array.from(d.working.getElementsByTagName('descriptif_simplifie'))
  .filter((e) => textOf(e, 'enum_categorie_descriptif_simplifie_id') === cat).map((e) => textOf(e, 'description'));
const mur = (d: Dossier, i = 0) => getUid(objectsOfKind(d.working, KIND_BY_KEY.get('mur')!)[i])!;

describe('descriptifs simplifiés tenus à jour', () => {
  it('garde les textes du logiciel tant que la description ne change pas', async () => {
    const xml = fixture('2600E0083091A.xml');
    const d = await Dossier.fromImport(xml, 'x.xml', nodeSchemas());
    const murs = descriptifs(d, '1');
    expect(murs.length).toBeGreaterThan(0);
    setFieldValue(d, mur(d), 'donnee_entree/surface_paroi_opaque', '12,5');
    expect(descriptifs(d, '1')).toEqual(murs);
  });

  it('régénère la seule catégorie dont la description change, et restaure l\'origine au retour', async () => {
    const d = await Dossier.fromImport(fixture('2600E0083091A.xml'), 'x.xml', nodeSchemas());
    const avant = { murs: descriptifs(d, '1'), baies: descriptifs(d, '4'), chauffage: descriptifs(d, '5') };
    const u = mur(d);
    const materiau = textOf(d.index().get(u)!, 'donnee_entree/enum_materiaux_structure_mur_id')!;
    setFieldValue(d, u, 'donnee_entree/enum_materiaux_structure_mur_id', '13');
    const murs = descriptifs(d, '1');
    expect(murs).not.toEqual(avant.murs);
    expect(murs.some((t) => /^Mur en béton banché/.test(t ?? ''))).toBe(true);
    expect(descriptifs(d, '4')).toEqual(avant.baies);
    expect(descriptifs(d, '5')).toEqual(avant.chauffage);
    // retour à la valeur d'origine : textes du logiciel restaurés
    setFieldValue(d, u, 'donnee_entree/enum_materiaux_structure_mur_id', materiau);
    expect(descriptifs(d, '1')).toEqual(avant.murs);
    // annulations
    d.undo();
    d.undo();
    expect(descriptifs(d, '1')).toEqual(avant.murs);
  });

  it('rédige les descriptifs d\'un dossier neuf à mesure de la saisie', async () => {
    const schemas = nodeSchemas();
    const schema = (await schemas.forVersion('2.6'))!;
    const d = await Dossier.fromSkeleton(buildSkeleton(schema, { version: '2.6', methodeApplication: '1' }), 'Neuf', schemas);
    const { addObject } = await import('../src/app/core/edition/editor');
    const u = addObject(d, 'mur', { name: 'Mur nord' });
    setFieldValue(d, u, 'donnee_entree/enum_materiaux_structure_mur_id', '8');
    setFieldValue(d, u, 'donnee_entree/epaisseur_structure', '22');
    setFieldValue(d, u, 'donnee_entree/enum_type_isolation_id', '3');
    setFieldValue(d, u, 'donnee_entree/resistance_isolation', '3,7');
    setFieldValue(d, u, 'donnee_entree/enum_type_adjacence_id', '1');
    expect(descriptifs(d, '1')).toEqual(['Mur en briques pleines simples d\'épaisseur 22 cm avec isolation intérieure (R = 3,7 m².K/W) donnant sur l\'extérieur']);
    expect(d.exportXml()).toMatch(/<descriptif_simplifie_collection>\s*<descriptif_simplifie>\s*<description>Mur en briques/);
  });

  it('rédige baies, portes, chauffage, ECS et ventilation dans le style des logiciels', async () => {
    const d = await Dossier.fromImport(fixture('2600E0083091A.xml'), 'x.xml', nodeSchemas());
    const baies = genererDescriptifs(d, '4');
    expect(baies.some((t) => /^(Fenêtres|Portes-fenêtres) .+, orientées (Sud|Nord|Est|Ouest), (simple|double|triple) vitrage/.test(t))).toBe(true);
    expect(genererDescriptifs(d, '5').length).toBeGreaterThan(0);
    expect(genererDescriptifs(d, '6').length).toBeGreaterThan(0);
    for (const t of [...baies, ...genererDescriptifs(d, '8')]) expect(t).not.toMatch(/undefined|null/);
  });
});
