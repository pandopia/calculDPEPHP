import { beforeAll, describe, expect, it } from 'vitest';
import { validateXML, memoryPages } from 'xmllint-wasm';
import { Dossier } from '../src/app/core/state/dossier';
import { buildSkeleton } from '../src/app/core/state/skeleton';
import { SchemaRegistry } from '../src/app/core/schema/schema-registry';
import { buildFiche, buildModel, FieldView, GroupView } from '../src/app/core/metier/model';
import {
  addGenericItem, addObject, executeDeletion, executeDuplication, planDeletion, planDuplication, setAttribute, setFieldState, setFieldValue, setReference,
} from '../src/app/core/edition/editor';
import { computeChanges } from '../src/app/core/edition/change-set';
import { exportXml } from '../src/app/core/xml/serializer';
import { parseXml } from '../src/app/core/xml/safe-xml';
import { semanticDiff } from '../src/app/core/xml/semantic-compare';
import { RelationGraph } from '../src/app/core/metier/relations';
import { runXsdValidation, translateXsdMessage, XsdEngine, parseXmllintErrors } from '../src/app/core/validation/xsd-validation';
import { fixture, nodeSchemas } from './helpers';

let schemas: SchemaRegistry;
beforeAll(() => {
  schemas = nodeSchemas();
});

const MAISON = '2600E0083091A.xml';
const MULTI = '2400E0020849A.xml';

async function open(name: string): Promise<Dossier> {
  return Dossier.fromImport(fixture(name), name, schemas);
}

function count(doc: Document, tag: string): number {
  return doc.getElementsByTagName(tag).length;
}

function allFields(groups: GroupView[]): FieldView[] {
  return groups.flatMap((g) => [...g.fields, ...g.notApplicable, ...allFields(g.groups), ...g.repeatables.flatMap((r) => r.items.flatMap((i) => allFields([i.group])))]);
}

function uidsOf(d: Dossier, kind: string): string[] {
  return buildModel(d).tabs.flatMap((t) => t.sections).find((s) => s.def.kind === kind)!.uids;
}

/** Aucune référence (mode id) créée par l'éditeur ne doit être non résolue. */
function brokenRefs(d: Dossier, before: Dossier): string[] {
  const g = new RelationGraph(d.working);
  const g0 = new RelationGraph(before.baseline);
  const preexisting = new Set([...g0.outgoing.values()].flat().filter((l) => l.status !== 'ok').map((l) => l.ref.key + l.value));
  return [...g.outgoing.values()].flat().filter((l) => l.status !== 'ok' && !preexisting.has(l.ref.key + l.value)).map((l) => `${l.ref.key}=${l.value}`);
}

describe('import et affichage métier', () => {
  it('détecte le format, la version et le schéma', async () => {
    const d = await open(MAISON);
    expect(d.format.famille).toBe('dpe_logement_existant');
    expect(d.format.enumVersionId).toBe('2.6');
    expect(d.format.xsd).toBe('DPEv2.6.xsd');
    expect(d.format.enTeteObservatoire).toBe(true);
    expect(d.format.niveauSupport).toBe('complet');
    const d23 = await open(MULTI);
    expect(d23.format.xsd).toBe('DPEv2.2.xsd');
  });

  it('retrouve chaque objet dans le bon onglet', async () => {
    const d = await open(MULTI);
    const model = buildModel(d);
    const section = (k: string) => model.tabs.flatMap((t) => t.sections).find((s) => s.def.kind === k)!;
    expect(section('mur').count).toBe(count(d.working, 'mur'));
    expect(section('mur').tab).toBe('enveloppe');
    expect(section('baie_vitree').count).toBe(count(d.working, 'baie_vitree'));
    expect(section('installation_chauffage').count).toBe(2);
    expect(section('installation_chauffage').tab).toBe('systemes');
    expect(section('climatisation').tab).toBe('systemes');
    const gens = [...model.objects.values()].filter((o) => o.kind?.key === 'generateur_chauffage');
    expect(gens.length).toBe(3);
    expect(gens.every((g) => g.parentUid && model.objects.get(g.parentUid)!.kind!.key === 'installation_chauffage')).toBe(true);
    const resultats = model.tabs.find((t) => t.def.key === 'resultats')!;
    expect(resultats.sections.find((s) => s.def.key === 'ep_conso')!.singletonUid).toBeTruthy();
  });

  it('affiche les ouvertures d\'un mur et sa paroi support dans les deux sens', async () => {
    const d = await open(MULTI);
    const model = buildModel(d);
    const baie = [...model.objects.values()].find((o) => o.kind?.key === 'baie_vitree')!;
    const fiche = buildFiche(d, model, baie.uid)!;
    const paroi = fiche.outgoing.find((r) => r.refKey === 'paroi')!;
    expect(paroi.items[0].status).toBe('ok');
    const mur = buildFiche(d, model, paroi.items[0].uid)!;
    expect(mur.incoming.find((r) => r.refKey === 'paroi')!.items.some((i) => i.uid === baie.uid)).toBe(true);
    const champ = allFields(fiche.groups).find((f) => f.rel === 'donnee_entree/reference_paroi')!;
    expect(champ.input).toBe('reference');
    expect(champ.ref!.candidates.length).toBeGreaterThan(0);
    expect(champ.ref!.candidates.every((c) => !/^n[0-9a-z]+$/.test(c.label))).toBe(true);
  });

  it('restitue le générateur mixte partagé entre chauffage et ECS', async () => {
    const d = await open(MULTI);
    const g = new RelationGraph(d.working);
    const mixtes = [...g.outgoing.values()].flat().filter((l) => l.ref.key === 'mixte');
    expect(mixtes.length).toBeGreaterThan(0);
    const ok = mixtes.find((l) => l.status === 'ok')!;
    expect(ok).toBeTruthy();
    const kinds = new Set([l2k(g, ok.fromUid), ...ok.targets.map((t) => l2k(g, g.objects.find((o) => o.el === t)!.uid))]);
    expect(kinds).toEqual(new Set(['generateur_chauffage', 'generateur_ecs']));
  });
});

function l2k(g: RelationGraph, uid: string): string {
  return g.byUid.get(uid)!.kind.key;
}

describe('export sans perte', () => {
  it('réexporte sans modification sans différence de contenu', async () => {
    for (const name of [MAISON, MULTI, '2400E0571888V.xml']) {
      const d = await open(name);
      expect(semanticDiff(parseXml(fixture(name)), parseXml(exportXml(d.working)))).toEqual([]);
      expect(computeChanges(d)).toEqual([]);
    }
  });

  it('une modification de champ ne change que ce champ (virgule décimale acceptée, chiffres conservés)', async () => {
    const d = await open(MAISON);
    const mur = uidsOf(d, 'mur')[0];
    setFieldValue(d, mur, 'donnee_entree/surface_paroi_opaque', '12,3456789');
    const diff = semanticDiff(parseXml(fixture(MAISON)), parseXml(exportXml(d.working)));
    expect(diff).toHaveLength(1);
    expect(diff[0]).toMatch(/surface_paroi_opaque.*12\.3456789/);
    const changes = computeChanges(d);
    expect(changes).toHaveLength(1);
    expect(changes[0].champ).toBe('Surface opaque');
    expect(changes[0].apres).toContain('12,3456789');
  });

  it('conserve une section inconnue après modification d\'un autre élément', async () => {
    const xml = fixture(MAISON).replace('</administratif>', '<extension_logiciel xmlns="urn:editeur-tiers" code="A"><bloc>valeur &amp; texte</bloc></extension_logiciel></administratif>')
      .replace('</dpe>', '<donnees_editeur><x>1</x></donnees_editeur></dpe>');
    const d = await Dossier.fromImport(xml, 'x.xml', schemas);
    const model = buildModel(d);
    expect(model.issues.some((i) => /extension_logiciel/.test(i.message))).toBe(true);
    expect(model.otherSections.map((s) => s.label)).toContain('Donnees editeur');
    setFieldValue(d, uidsOf(d, 'mur')[0], 'donnee_entree/description', 'Mur nord');
    const out = exportXml(d.working);
    expect(out).toContain('<extension_logiciel xmlns="urn:editeur-tiers" code="A">');
    expect(out).toContain('<bloc>valeur &amp; texte</bloc>');
    expect(out).toContain('<donnees_editeur>');
    expect(semanticDiff(parseXml(xml), parseXml(out))).toHaveLength(1);
  });

  it('réimporte un XML exporté et retrouve les mêmes données', async () => {
    const d = await open(MULTI);
    setFieldValue(d, uidsOf(d, 'mur')[1], 'donnee_entree/epaisseur_structure', '42');
    const exported = exportXml(d.working);
    const d2 = await Dossier.fromImport(exported, 'reimport.xml', schemas);
    const m1 = buildModel(d);
    const m2 = buildModel(d2);
    const flat = (m: ReturnType<typeof buildModel>) => [...m.objects.values()].map((o) => `${o.kind?.key ?? o.label}|${o.title}|${o.summary.map((s) => s.value).join(',')}`).sort();
    expect(flat(m2)).toEqual(flat(m1));
    expect(semanticDiff(parseXml(exported), parseXml(exportXml(d2.working)))).toEqual([]);
  });
});

describe('états de valeur', () => {
  it('distingue absent, vide, nul explicite et zéro', async () => {
    const d = await open(MAISON);
    const mur = uidsOf(d, 'mur')[0];
    const rel = 'donnee_entree/surface_aiu';
    const field = () => allFields(buildFiche(d, buildModel(d), mur)!.groups).find((f) => f.rel === rel)!;
    setFieldValue(d, mur, rel, '0');
    expect(field().state).toBe('valeur');
    expect(field().raw).toBe('0');
    setFieldState(d, mur, rel, 'nil');
    expect(field().state).toBe('nil');
    expect(exportXml(d.working)).toMatch(/<surface_aiu xsi:nil="true"\/>/);
    setFieldState(d, mur, rel, 'vide');
    expect(field().state).toBe('absent'); // une balise vide est traitée comme non renseignée
    expect(d.exportXml()).not.toMatch(/<surface_aiu/); // et retirée à l'export
    setFieldState(d, mur, rel, 'absent');
    expect(field().state).toBe('absent');
  });

  it('refuse une saisie non représentable, accepte et signale un hors bornes', async () => {
    const d = await open(MAISON);
    const mur = uidsOf(d, 'mur')[0];
    expect(() => setFieldValue(d, mur, 'donnee_entree/surface_paroi_opaque', 'douze')).toThrow(/Nombre attendu/);
    const r = setFieldValue(d, mur, 'donnee_entree/enum_orientation_id', '99');
    expect(r.warnings.length).toBeGreaterThan(0);
  });

  it('conserve et signale un code d\'énumération inconnu', async () => {
    const xml = fixture(MAISON).replace(/<enum_orientation_id>\d+<\/enum_orientation_id>/, '<enum_orientation_id>77</enum_orientation_id>');
    const d = await Dossier.fromImport(xml, 'x.xml', schemas);
    const model = buildModel(d);
    expect(model.issues.some((i) => /orientation/i.test(i.message) && i.field === 'donnee_entree/enum_orientation_id')).toBe(true);
    expect(exportXml(d.working)).toContain('<enum_orientation_id>77</enum_orientation_id>');
    const f = allFields(buildFiche(d, model, uidsOf(d, 'mur')[0])!.groups).find((x) => x.name === 'enum_orientation_id')!;
    expect(f.unknownCode).toBe(true);
  });
});

describe('objets et relations', () => {
  it('ajoute une fenêtre depuis un mur : rattachement automatique', async () => {
    const d = await open(MAISON);
    const mur = uidsOf(d, 'mur')[0];
    const before = uidsOf(d, 'baie_vitree').length;
    const baie = addObject(d, 'baie_vitree', { name: 'Fenêtre séjour', link: { refKey: 'paroi', targetUid: mur } });
    expect(uidsOf(d, 'baie_vitree')).toHaveLength(before + 1);
    const model = buildModel(d);
    const fiche = buildFiche(d, model, baie)!;
    expect(fiche.outgoing.find((r) => r.refKey === 'paroi')!.items[0].uid).toBe(mur);
    expect(buildFiche(d, model, mur)!.incoming.find((r) => r.refKey === 'paroi')!.items.map((i) => i.uid)).toContain(baie);
    expect(brokenRefs(d, d)).toEqual([]);
    // aucune valeur physique inventée : seuls référence, description et rattachement sont écrits
    const xml = exportXml(d.working);
    const added = xml.slice(xml.lastIndexOf('<baie_vitree>'), xml.indexOf('</baie_vitree>', xml.lastIndexOf('<baie_vitree>')));
    expect(added).toContain('<description>Fenêtre séjour</description>');
    expect(added).not.toMatch(/<surface_totale_baie>/);
    expect(model.issues.some((i) => i.uid === baie && i.niveau === 'completude')).toBe(true);
  });

  it('duplique un mur avec ses ouvertures : identifiants neufs, liens internes remappés', async () => {
    const d = await open(MULTI);
    const g = new RelationGraph(d.working);
    const mur = g.objects.find((o) => o.kind.key === 'mur' && (g.incoming.get(o.uid) ?? []).some((l) => l.ref.key === 'paroi'))!;
    const openings = (g.incoming.get(mur.uid) ?? []).filter((l) => l.ref.key === 'paroi').length;
    const plan = planDuplication(d, mur.uid, { withOpenings: true });
    expect(plan.openings).toHaveLength(openings);
    expect(plan.remapped.length).toBe(openings);
    const copy = executeDuplication(d, mur.uid, { withOpenings: true });
    const g2 = new RelationGraph(d.working);
    expect(g2.duplicateReferences.size).toBe(g.duplicateReferences.size);
    expect((g2.incoming.get(copy) ?? []).filter((l) => l.ref.key === 'paroi')).toHaveLength(openings);
    expect((g2.incoming.get(mur.uid) ?? []).filter((l) => l.ref.key === 'paroi')).toHaveLength(openings);
    expect(brokenRefs(d, d)).toEqual([]);
  });

  it('duplique une installation : générateurs copiés, lien mixte retiré sauf choix contraire', async () => {
    const d = await open(MULTI);
    const g = new RelationGraph(d.working);
    const inst = g.objects.find((o) => o.kind.key === 'installation_chauffage' && g.objects.some((x) => x.kind.key === 'generateur_chauffage' && o.el.contains(x.el) && (g.outgoing.get(x.uid) ?? []).some((l) => l.ref.key === 'mixte')))!;
    const plan = planDuplication(d, inst.uid);
    expect(plan.copies.length).toBeGreaterThan(1);
    expect(plan.removed.some((r) => /mixte/i.test(r))).toBe(true);
    const copy = executeDuplication(d, inst.uid);
    const g2 = new RelationGraph(d.working);
    const copiedGens = g2.objects.filter((o) => o.kind.key === 'generateur_chauffage' && g2.byUid.get(copy)!.el.contains(o.el));
    expect(copiedGens.length).toBeGreaterThan(0);
    expect(copiedGens.every((x) => !(g2.outgoing.get(x.uid) ?? []).some((l) => l.ref.key === 'mixte'))).toBe(true);
  });

  it('supprime un mur : dépendances présentées, aucune référence cassée', async () => {
    const d = await open(MULTI);
    const g = new RelationGraph(d.working);
    const mur = g.objects.find((o) => o.kind.key === 'mur' && (g.incoming.get(o.uid) ?? []).some((l) => l.ref.key === 'paroi'))!;
    const plan = planDeletion(d, mur.uid);
    expect(plan.dependencies.length).toBeGreaterThan(0);
    expect(plan.dependencies.every((x) => x.resolution.action === 'detacher')).toBe(true);
    const other = plan.dependencies[0].alternatives[0].uid;
    const choices = Object.fromEntries(plan.dependencies.map((x, i) => [x.key, i === 0 ? { action: 'reaffecter' as const, targetUid: other } : { action: 'supprimer' as const }]));
    const plan2 = planDeletion(d, mur.uid, choices);
    expect(plan2.deleted.length).toBeGreaterThan(1);
    executeDeletion(d, mur.uid, choices);
    expect(new RelationGraph(d.working).byUid.has(mur.uid)).toBe(false);
    expect(brokenRefs(d, d)).toEqual([]);
  });

  it('supprime une partie de générateur mixte : la référence partagée est retirée de l\'autre partie', async () => {
    const d = await open(MULTI);
    const g = new RelationGraph(d.working);
    const link = [...g.outgoing.values()].flat().find((l) => l.ref.key === 'mixte' && l.status === 'ok')!;
    const plan = planDeletion(d, link.fromUid);
    expect(plan.warnings.some((w) => /mixte/.test(w))).toBe(true);
    executeDeletion(d, link.fromUid, {});
    const g2 = new RelationGraph(d.working);
    expect([...g2.outgoing.values()].flat().filter((l) => l.ref.key === 'mixte' && l.value === link.value && l.status !== 'ok')).toEqual([]);
  });

  it('associe deux générateurs en mixte depuis l\'interface', async () => {
    const d = await open(MAISON);
    const ch = uidsOf(d, 'installation_chauffage')[0];
    const ecs = uidsOf(d, 'installation_ecs')[0];
    const gch = buildModel(d).objects.get(ch)!.childUids.find((u) => buildModel(d).objects.get(u)!.kind!.key === 'generateur_chauffage')!;
    const gecs = buildModel(d).objects.get(ecs)!.childUids.find((u) => buildModel(d).objects.get(u)!.kind!.key === 'generateur_ecs')!;
    setReference(d, gch, 'mixte', gecs);
    const g = new RelationGraph(d.working);
    expect((g.outgoing.get(gch) ?? []).find((l) => l.ref.key === 'mixte')!.targets).toHaveLength(1);
  });
});

describe('résultats, historique et contrôles', () => {
  it('marque les résultats à recalculer après une donnée d\'entrée, pas après une description', async () => {
    const d = await open(MAISON);
    const mur = uidsOf(d, 'mur')[0];
    setFieldValue(d, mur, 'donnee_entree/description', 'Mur sud renommé');
    expect(d.meta.resultatsObsoletes).toBe(false);
    setFieldValue(d, mur, 'donnee_entree/epaisseur_structure', '30');
    expect(d.meta.resultatsObsoletes).toBe(true);
    expect(d.meta.motifsObsolescence[0]).toMatch(/Épaisseur de la structure/);
  });

  it('annule et rétablit', async () => {
    const d = await open(MAISON);
    const mur = uidsOf(d, 'mur')[0];
    const before = exportXml(d.working);
    setFieldValue(d, mur, 'donnee_entree/epaisseur_structure', '30');
    d.undo();
    expect(exportXml(d.working)).toBe(before);
    expect(d.meta.resultatsObsoletes).toBe(false);
    d.redo();
    expect(exportXml(d.working)).toContain('<epaisseur_structure>30</epaisseur_structure>');
  });

  it('chaque anomalie mène à un objet et à un champ affichés', async () => {
    const d = await open(MULTI);
    const model = buildModel(d);
    const withField = model.issues.filter((i) => i.uid && i.field);
    expect(withField.length).toBeGreaterThan(0);
    for (const i of withField.slice(0, 80)) {
      const fiche = buildFiche(d, model, i.uid!)!;
      expect(fiche).toBeTruthy();
      expect(model.objects.has(i.uid!)).toBe(true);
    }
  });

  it('valide contre le XSD et rattache les erreurs aux champs', async () => {
    const engine: XsdEngine = {
      async validate(xml, xsd) {
        const r = await validateXML({ xml: [{ fileName: 'd.xml', contents: xml }], schema: [xsd], maxMemoryPages: memoryPages.GiB });
        return { valid: r.valid, errors: parseXmllintErrors(r.rawOutput) };
      },
    };
    const d = await open(MAISON);
    const mur = uidsOf(d, 'mur')[0];
    setFieldValue(d, mur, 'donnee_entree/enum_orientation_id', '99');
    const report = await runXsdValidation(d, engine);
    expect(report.excludedHeader).toBe(true);
    const err = report.issues.find((i) => i.uid === mur && i.field === 'donnee_entree/enum_orientation_id');
    expect(err).toBeTruthy();
    expect(err!.message).toMatch(/Orientation/);
    expect(report.issues.every((i) => !/numero_dpe/.test(i.detail ?? ''))).toBe(true);
  });

  it('traduit les messages libxml', () => {
    expect(translateXsdMessage("Schemas validity error : Element 'hsp': [facet 'minExclusive'] The value '0' must be greater than '0'.")).toMatch(/Hauteur sous plafond/);
    expect(translateXsdMessage("Element 'mur': Missing child element(s). Expected is one of ( donnee_intermediaire )."))
      .toMatch(/^« Mur » : éléments obligatoires manquants/);
  });
});

describe('nouveau dossier', () => {
  it('crée un dossier vierge sans valeur inventée, le complète partiellement et le reprend', async () => {
    const schema = (await schemas.forVersion('2.6'))!;
    const doc = buildSkeleton(schema, { version: '2.6', methodeApplication: '1' });
    const d = await Dossier.fromSkeleton(doc, 'Maison test', schemas);
    expect(d.format.niveauSupport).toBe('complet');
    const xml = exportXml(d.working);
    const values = [...xml.matchAll(/<([a-z_0-9]+)>([^<]+)<\/\1>/g)].map((m) => m[1]);
    expect(values.sort()).toEqual(['enum_methode_application_dpe_log_id', 'enum_modele_dpe_id', 'enum_version_id']);
    const model = buildModel(d);
    expect(model.tabs.flatMap((t) => t.sections).find((x) => x.def.key === 'ventilations')!.status).toBe('a_completer');
    expect(model.tabs.find((t) => t.def.key === 'resultats')!.sections.find((s) => s.def.key === 'ep_conso')!.status).toBe('absent');
    expect(model.issues.filter((i) => i.niveau === 'completude').length).toBeGreaterThan(10);

    setAttribute(d, d.working.documentElement.getAttributeNS('urn:calculdpe:editeur', 'uid'), 'version', '2');
    const mur = addObject(d, 'mur', { name: 'Mur extérieur nord' });
    setFieldValue(d, mur, 'donnee_entree/surface_paroi_opaque', '18,5');
    const cg = buildModel(d).tabs.find((t) => t.def.key === 'batiment')!.sections[0].singletonUid!;
    setFieldValue(d, cg, 'hsp', '2,5');

    const draft = JSON.parse(JSON.stringify(d.toDraft()));
    const reopened = await Dossier.fromDraft(draft, schemas);
    expect(exportXml(reopened.working)).toBe(exportXml(d.working));
    expect(buildModel(reopened).objects.get(mur)!.title).toBe('Mur extérieur nord');
    // les identifiants internes continuent sans collision
    const mur2 = addObject(reopened, 'mur', { name: 'Mur sud' });
    expect(mur2).not.toBe(mur);
  });
});

describe('éléments répétables génériques', () => {
  it('ajoute deux masques lointains et édite le second sans toucher au premier', async () => {
    const d = await open(MULTI);
    const baie = uidsOf(d, 'baie_vitree')[0];
    const rel = 'donnee_entree/masque_lointain_non_homogene_collection/masque_lointain_non_homogene';
    const m1 = addGenericItem(d, baie, rel);
    const m2 = addGenericItem(d, baie, rel);
    expect(m1).not.toBe(m2);
    const fiche = buildFiche(d, buildModel(d), baie)!;
    const rep = fiche.groups.flatMap((g) => [g, ...g.groups]).flatMap((g) => g.repeatables).find((r) => r.name === 'masque_lointain_non_homogene')!;
    expect(rep.items.map((i) => i.uid)).toEqual([m1, m2]);
    const f = rep.items[1].group.fields[0];
    setFieldValue(d, m2, f.rel, '3');
    const els = d.working.getElementsByTagName('masque_lointain_non_homogene');
    const last = els.length - 1;
    expect(els[last].getElementsByTagName(f.name)[0]?.textContent).toBe('3');
    expect(els[last - 1].getElementsByTagName(f.name).length).toBe(0);
  });
});

describe('formats hors périmètre', () => {
  it('ouvre un DPE neuf en vue générique, sans objets métier, et l\'exporte sans perte', async () => {
    const xml = fixture(MAISON).replace(/<logement>/, '<logement_neuf>').replace(/<\/logement>/, '</logement_neuf>');
    const d = await Dossier.fromImport(xml, 'neuf.xml', schemas);
    expect(d.format.famille).toBe('dpe_logement_neuf');
    expect(d.format.niveauSupport).toBe('generique');
    const model = buildModel(d);
    expect(model.otherSections.map((s) => s.path)).toContain('/dpe/logement_neuf');
    expect(buildFiche(d, model, model.otherSections.find((s) => s.path === '/dpe/logement_neuf')!.uid)).toBeTruthy();
    expect(() => addObject(d, 'mur')).toThrow(/réservé/);
    expect(semanticDiff(parseXml(xml), parseXml(exportXml(d.working)))).toEqual([]);
  });

  it('ne migre pas une version inconnue et la signale', async () => {
    const xml = fixture(MAISON).replace('<enum_version_id>2.6</enum_version_id>', '<enum_version_id>9.9</enum_version_id>');
    const d = await Dossier.fromImport(xml, 'v99.xml', schemas);
    expect(d.schema).toBeNull();
    expect(d.format.niveauSupport).toBe('generique');
    expect(d.format.messages.join(' ')).toMatch(/inconnue.*pas migré/);
    expect(exportXml(d.working)).toContain('<enum_version_id>9.9</enum_version_id>');
  });

  it('refuse un audit énergétique', async () => {
    const d = await Dossier.fromImport('<audit><administratif/></audit>', 'audit.xml', schemas);
    expect(d.format.famille).toBe('audit');
    expect(d.format.niveauSupport).toBe('aucun');
  });
});

describe('balises vides de remplissage', () => {
  it('les ignore dans les contrôles et les retire à l\'export, sans rien changer d\'autre', async () => {
    const xml = fixture(MAISON)
      .replace('<production_elec_enr>', '<production_elec_enr_x>').replace('</administratif>', '<dpe_a_remplacer/></administratif>');
    const d = await Dossier.fromImport(xml.replace('<production_elec_enr_x>', '<production_elec_enr>'), 'x.xml', schemas);
    // bloc facultatif vide, comme le produisent certains logiciels
    const logement = d.working.getElementsByTagName('logement')[0];
    const pe = d.working.getElementsByTagName('production_elec_enr')[0] ?? logement.appendChild(d.working.createElementNS(null, 'production_elec_enr'));
    while (pe.firstChild) pe.firstChild.remove();
    d.revision++;
    const model = buildModel(d);
    expect(model.issues.filter((i) => /vide/.test(i.message))).toEqual([]);
    expect(model.issues.filter((i) => /Production|Donnee entree|Panneaux/i.test(i.message))).toEqual([]);
    const sec = model.tabs.flatMap((t) => t.sections).find((s) => s.def.key === 'production_elec')!;
    expect(sec.status).toBe('vide');
    const out = d.exportXml();
    expect(out).not.toContain('<production_elec_enr');
    expect(out).not.toMatch(/<dpe_a_remplacer\/>/);
    const before = parseXml(exportXml(d.working)).getElementsByTagName('*').length;
    expect(parseXml(out).getElementsByTagName('*').length).toBe(before - d.prunable().size);
    // une collection obligatoire vide (mur_collection 1..1) est conservée
    expect(out).toContain('<mur_collection>');
  });

  it('un bloc d\'informations ne liste pas les objets des autres onglets comme relations', async () => {
    const d = await open(MAISON);
    const model = buildModel(d);
    const cg = model.tabs.find((t) => t.def.key === 'batiment')!.sections[0].singletonUid!;
    expect(buildFiche(d, model, cg)!.children).toEqual([]);
    const pe = model.tabs.flatMap((t) => t.sections).find((s) => s.def.key === 'production_elec')!.singletonUid;
    if (pe) expect(buildFiche(d, model, pe)!.children.map((c) => c.kind.key)).toEqual(['panneaux_pv']);
  });
});

describe('dossier vierge : objets par défaut', () => {
  it('contient plancher bas, plancher haut, 4 murs orientés, ventilation, chauffage et ECS complets', async () => {
    const schemas = nodeSchemas();
    const { addDefaultObjects } = await import('../src/app/core/state/skeleton');
    const schema = (await schemas.forVersion('2.6'))!;
    const draft = await Dossier.fromSkeleton(buildSkeleton(schema, { version: '2.6', methodeApplication: '1' }), 'Maison', schemas);
    addDefaultObjects(draft);
    const d = await Dossier.fromSkeleton(draft.working, 'Maison', schemas);
    const model = buildModel(d);
    const count = (k: string) => [...model.objects.values()].filter((o) => o.kind?.key === k).length;
    expect([count('plancher_bas'), count('plancher_haut'), count('mur'), count('ventilation')]).toEqual([1, 1, 4, 1]);
    expect([count('installation_chauffage'), count('generateur_chauffage'), count('emetteur_chauffage')]).toEqual([1, 1, 1]);
    expect([count('installation_ecs'), count('generateur_ecs')]).toEqual([1, 1]);
    const orientations = [...model.objects.values()].filter((o) => o.kind?.key === 'mur').map((o) => o.summary.find((s) => s.label === 'Orientation')?.value);
    expect(orientations.sort()).toEqual(['Est', 'Nord', 'Ouest', 'Sud']);
    // état initial : rien n'apparaît comme modification, aucune référence cassée, uid uniques
    expect(computeChanges(d)).toEqual([]);
    expect(new RelationGraph(d.working).duplicateReferences.size).toBe(0);
    expect(d.meta.resultatsObsoletes).toBe(false);
    const gen = [...model.objects.values()].find((o) => o.kind?.key === 'generateur_chauffage')!;
    expect(model.objects.get(gen.parentUid!)!.kind!.key).toBe('installation_chauffage');
    expect(model.tabs.flatMap((t) => t.sections).find((s) => s.def.key === 'ventilations')!.status).not.toBe('vide');
  });
});

describe('balises hors schéma', () => {
  it('se retirent une à une ou toutes, sans toucher au reste, et s\'annulent', async () => {
    const { removeUnknown, removeAllUnknown, unknownElements } = await import('../src/app/core/edition/editor');
    // ce DPE réel contient déjà des balises data_complementaires écrites par son logiciel
    const xml = fixture(MAISON).replace('</administratif>', '<extension_logiciel>a</extension_logiciel></administratif>');
    const d = await Dossier.fromImport(xml, 'x.xml', schemas);
    const initial = unknownElements(d);
    expect(initial.map((e) => e.localName)).toContain('extension_logiciel');
    expect(initial.filter((e) => e.localName === 'data_complementaires').length).toBeGreaterThan(0);
    const owner = buildModel(d).objects.get(nearestOwnerUid(initial.find((e) => e.localName === 'data_complementaires')!))!;
    const fiche = buildFiche(d, buildModel(d), owner.uid)!;
    const u = fiche.groups.flatMap((g) => [g, ...g.groups]).flatMap((g) => g.unknown).find((x) => x.name === 'data_complementaires')!;
    removeUnknown(d, owner.uid, u.rel!);
    expect(unknownElements(d)).toHaveLength(initial.length - 1);
    expect(() => removeUnknown(d, owner.uid, 'donnee_entree')).toThrow(/hors schéma/);
    expect(removeAllUnknown(d)).toBe(initial.length - 1);
    const out = exportXml(d.working);
    expect(out).not.toContain('data_complementaires');
    expect(out).not.toContain('extension_logiciel');
    // rien d'autre n'a changé : identique à l'original privé de ses balises hors schéma
    const ref = parseXml(xml);
    for (const t of ['data_complementaires', 'extension_logiciel']) for (const e of Array.from(ref.getElementsByTagName(t))) e.remove();
    expect(semanticDiff(ref, parseXml(out))).toEqual([]);
    d.undo();
    d.undo();
    expect(unknownElements(d)).toHaveLength(initial.length);
  });
});

function nearestOwnerUid(el: Element): string {
  for (let n: Element | null = el.parentElement; n; n = n.parentElement) {
    const k = n.localName;
    if (['generateur_ecs', 'generateur_chauffage', 'installation_ecs', 'installation_chauffage', 'ventilation', 'emetteur_chauffage', 'climatisation'].includes(k)) {
      return n.getAttributeNS('urn:calculdpe:editeur', 'uid')!;
    }
  }
  throw new Error('propriétaire introuvable');
}
