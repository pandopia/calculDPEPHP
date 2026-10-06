import { Dossier } from '../state/dossier';
import { SchemaHandle, schemaPath } from '../schema/schema-registry';
import { collectionFor, createElement, ensurePath, objectsOfKind, setLeafText, textOf } from '../edition/doc-ops';
import { childElements, resolvePath } from '../xml/safe-xml';
import { annotate, getUid } from '../xml/uid';
import { KIND_BY_KEY } from './catalog';
import { fieldMeta } from './field-meta';

/**
 * Origine des données saisies, d'après les fiches techniques du XML.
 *
 * Le XSD ADEME décrit l'origine de chaque donnée dans `fiche_technique` :
 * une fiche par composant (catégorie : murs, planchers, baies…), une ligne par
 * paramètre (`description` libre « Libellé: valeur », `valeur`,
 * `enum_origine_donnee_id`). Aucun lien formel ne relie une ligne au champ
 * qu'elle documente : on le reconstitue.
 *
 *  1. Fiche → objet : dans la catégorie, chaque fiche va à l'objet dont les
 *     valeurs concordent le mieux avec les siennes (l'ordre des fiches ne suit
 *     pas toujours celui des objets), sinon à l'objet de même rang.
 *  2. Ligne → champ : table des libellés employés par les logiciels (mesurés
 *     sur 389 DPE réels de l'observatoire, à 94 % LICIEL), puis libellé du
 *     champ lui-même (lignes créées par l'éditeur).
 *
 * Les lignes non rattachées restent visibles dans la section « Fiches
 * techniques ». Rien n'est jamais modifié sans action de l'utilisateur.
 */

export const ORIGINES: { code: string; lettre: string; label: string }[] = [
  { code: '1', lettre: 'D', label: 'Valeur par défaut' },
  { code: '2', lettre: 'M', label: 'Mesuré/observé' },
  { code: '3', lettre: 'J', label: 'Issu d\'un document justificatif autorisé' },
  { code: '4', lettre: 'L', label: 'Obtenu en ligne' },
  { code: '5', lettre: 'E', label: 'Estimé' },
  { code: '6', lettre: 'P', label: 'Valeur par défaut pénalisante' },
];

export interface SourceDonnee {
  /** ligne de fiche technique (sous_fiche_technique) */
  ligneUid: string;
  ficheUid: string;
  libelle: string;
  valeur: string | null;
  origine: string | null;
}

/** Objets décrits par chaque catégorie de fiche technique. */
const CATEGORIES: Record<string, string> = {
  '1': 'mur', '2': 'plancher_bas', '3': 'plancher_haut', '4': 'baie_vitree', '5': 'porte', '6': 'pont_thermique',
  '7': 'installation_chauffage', '8': 'installation_ecs', '9': 'climatisation', '10': 'ventilation',
};
const CATEGORIE_DE_KIND: Record<string, string> = Object.fromEntries(Object.entries(CATEGORIES).map(([c, k]) => [k, c]));
/** sous-objets documentés par la fiche de leur installation */
const SOUS_OBJETS: Record<string, { gen: string; em?: string }> = {
  installation_chauffage: { gen: 'generateur_chauffage', em: 'emetteur_chauffage' },
  installation_ecs: { gen: 'generateur_ecs' },
};
const GENERAL = { cg: 'dpe/logement/caracteristique_generale', meteo: 'dpe/logement/meteo' };

const DE = 'donnee_entree/';
const PAROI = {
  'isolation': [DE + 'enum_type_isolation_id'],
  'type d\'adjacence': [DE + 'enum_type_adjacence_id'],
  'annee isolation': [DE + 'enum_periode_isolation_id'],
  'epaisseur isolant': [DE + 'epaisseur_isolation'],
  'resistance isolant': [DE + 'resistance_isolation'],
  'surface aiu': [DE + 'surface_aiu'],
  'surface aue': [DE + 'surface_aue'],
  'etat isolation des parois aiu': [DE + 'enum_cfg_isolation_lnc_id'],
  'etat isolation des parois aue': [DE + 'enum_cfg_isolation_lnc_id'],
};
const MENUISERIE = {
  'positionnement de la menuiserie': [DE + 'enum_type_pose_id'],
  'largeur du dormant menuiserie': [DE + 'largeur_dormant'],
  'presence de joints d\'etancheite': [DE + 'presence_joint'],
  'retour isolation autour menuiserie': [DE + 'presence_retour_isolation'],
  'type d\'adjacence': [DE + 'enum_type_adjacence_id'],
  'surface aiu': [DE + 'surface_aiu'],
  'surface aue': [DE + 'surface_aue'],
  'etat isolation des parois aiu': [DE + 'enum_cfg_isolation_lnc_id'],
  'etat isolation des parois aue': [DE + 'enum_cfg_isolation_lnc_id'],
};

/**
 * Libellés (normalisés) → champs candidats, par catégorie. Préfixes :
 * `gen:` générateur, `em:` émetteur de l'installation ; `cg:`/`meteo:`
 * caractéristiques générales et météo (catégorie « général »).
 */
export const LIBELLES: Record<string, Record<string, string[]>> = {
  '1': {
    ...PAROI,
    'materiau mur': [DE + 'enum_materiaux_structure_mur_id'],
    'surface du mur': [DE + 'surface_paroi_totale', DE + 'surface_paroi_opaque'],
    'epaisseur mur': [DE + 'epaisseur_structure'],
    'doublage rapporte avec lame d\'air': [DE + 'enum_type_doublage_id'],
    'orientation': [DE + 'enum_orientation_id'],
  },
  '2': {
    ...PAROI,
    'surface de plancher bas': [DE + 'surface_paroi_opaque'],
    'type de pb': [DE + 'enum_type_plancher_bas_id'],
    'perimetre plancher batiment deperditif': [DE + 'perimetre_ue'],
    'surface plancher batiment deperditif': [DE + 'surface_ue'],
  },
  '3': {
    ...PAROI,
    'surface de plancher haut': [DE + 'surface_paroi_opaque'],
    'surface': [DE + 'surface_paroi_opaque'],
    'type de ph': [DE + 'enum_type_plancher_haut_id'],
  },
  '4': {
    ...MENUISERIE,
    'surface de baies': [DE + 'surface_totale_baie'],
    'orientation des baies': [DE + 'enum_orientation_id'],
    'inclinaison vitrage': [DE + 'enum_inclinaison_vitrage_id'],
    'type ouverture': [DE + 'enum_type_baie_id'],
    'type de vitrage': [DE + 'enum_type_vitrage_id'],
    'type menuiserie': [DE + 'enum_type_materiaux_menuiserie_id'],
    'presence couche peu emissive': [DE + 'vitrage_vir'],
    'epaisseur lame air': [DE + 'epaisseur_lame'],
    'gaz de remplissage': [DE + 'enum_type_gaz_lame_id'],
    'type de fermeture': [DE + 'enum_type_fermeture_id'],
    'double fenetre': [DE + 'double_fenetre'],
    'type de masques proches': [DE + 'tv_coef_masque_proche_id'],
    'type volets': [DE + 'enum_type_fermeture_id'],
  },
  '5': {
    ...MENUISERIE,
    'type de porte': [DE + 'enum_type_porte_id'],
    'surface de porte': [DE + 'surface_porte'],
  },
  '6': {
    'longueur du pt': [DE + 'l'],
    'longueur du pont thermique': [DE + 'l'],
    'type pt': [DE + 'enum_type_liaison_id'],
    'type de pont thermique': [DE + 'enum_type_liaison_id'],
    'valeur pt k (saisie directe)': [DE + 'k_saisi'],
  },
  '7': {
    'type d\'installation de chauffage': [DE + 'enum_cfg_installation_ch_id'],
    'surface chauffee': [DE + 'surface_chauffee'],
    'nombre de niveaux desservis': [DE + 'nombre_niveau_installation_ch'],
    'type generateur': ['gen:' + DE + 'enum_type_generateur_ch_id'],
    'energie utilisee': ['gen:' + DE + 'enum_type_energie_id'],
    'type emetteur': ['em:' + DE + 'enum_type_emission_distribution_id'],
    'surface chauffee par l\'emetteur': ['em:' + DE + 'surface_chauffee'],
    'equipement intermittence': ['em:' + DE + 'enum_equipement_intermittence_id'],
    'temperature de distribution': ['em:' + DE + 'enum_temp_distribution_ch_id'],
    'type de chauffage': ['em:' + DE + 'enum_type_chauffage_id'],
    'annee installation emetteur': ['em:' + DE + 'enum_periode_installation_emetteur_id'],
  },
  '8': {
    'nombre de niveaux desservis': [DE + 'nombre_niveau_installation_ecs'],
    'type generateur': ['gen:' + DE + 'enum_type_generateur_ecs_id'],
    'energie utilisee': ['gen:' + DE + 'enum_type_energie_id'],
    'volume de stockage': ['gen:' + DE + 'volume_stockage'],
    'type de production': ['gen:' + DE + 'enum_type_stockage_ecs_id'],
  },
  '9': {
    'systeme': [DE + 'enum_type_generateur_fr_id'],
    'energie utilisee': [DE + 'enum_type_energie_id'],
    'surface de reference refroidie': [DE + 'surface_clim'],
    'surface habitable refroidie': [DE + 'surface_clim'],
    'annee installation equipement': [DE + 'enum_periode_installation_fr_id'],
  },
  '10': {
    'type de ventilation': [DE + 'enum_type_ventilation_id'],
    'facades exposees': [DE + 'plusieurs_facade_exposee'],
    'plusieurs facades exposees': [DE + 'plusieurs_facade_exposee'],
    'nombre de facades exposees': [DE + 'plusieurs_facade_exposee'],
  },
  '11': {
    'hauteur moyenne sous plafond': ['cg:hsp'],
    'annee de construction': ['cg:annee_construction'],
    'nombre de niveaux du logement': ['cg:nombre_niveau_logement'],
    'nombre de niveaux de l\'immeuble': ['cg:nombre_niveau_immeuble'],
    'nb. de logements du batiment': ['cg:nombre_appartement'],
    'surface de reference de l\'immeuble': ['cg:surface_habitable_immeuble'],
    'surface de reference du logement': ['cg:surface_habitable_logement'],
    'surface habitable du logement': ['cg:surface_habitable_logement'],
    'altitude': ['meteo:enum_classe_altitude_id'],
  },
};

export function normaliserLibelle(s: string): string {
  return s.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[’`]/g, '\'').toLowerCase().replace(/\s+/g, ' ').replace(/\s*:\s*$/, '').trim();
}

/** Index « uid de l'objet|champ » → ligne de fiche technique. */
export interface IndexSources {
  champs: Map<string, SourceDonnee>;
  /** fiche technique de chaque objet (création d'une ligne) */
  fiches: Map<string, string>;
  /** lignes rattachées à un champ */
  rattachees: Set<string>;
}

export function cleSource(uid: string, rel: string): string {
  return `${uid}|${rel}`;
}

interface Ligne { el: Element; uid: string; libelle: string; cle: string; valeur: string | null; origine: string | null }

function lignes(fiche: Element): Ligne[] {
  const coll = childElements(fiche).find((c) => c.localName === 'sous_fiche_technique_collection');
  return (coll ? childElements(coll) : []).filter((e) => e.localName === 'sous_fiche_technique').map((el) => {
    const description = textOf(el, 'description') ?? '';
    const i = description.indexOf(':');
    const libelle = (i >= 0 ? description.slice(0, i) : description).trim();
    return { el, uid: getUid(el) ?? '', libelle, cle: normaliserLibelle(libelle), valeur: textOf(el, 'valeur') ?? (i >= 0 ? description.slice(i + 1).trim() : null), origine: textOf(el, 'enum_origine_donnee_id') };
  });
}

/** Concordance d'une valeur de fiche (« 43,02 m² », « Mur en pierre… ») avec un champ du XML. */
export function concorde(valeur: string | null, leaf: Element | null, schema: SchemaHandle | null): boolean {
  if (!valeur || !leaf) return false;
  const raw = (leaf.textContent ?? '').trim();
  if (!raw) return false;
  const def = schema?.def(schemaPath(leaf)) ?? null;
  const v = normaliserLibelle(valeur);
  const labels = def?.enumLabels;
  if (labels && Object.keys(labels).join() === '0,1') return (raw === '1' && /^(oui|1|vrai)\b/.test(v)) || (raw === '0' && /^(non|0|faux)\b/.test(v));
  if (labels) {
    const l = normaliserLibelle(labels[raw] ?? '');
    return !!l && (l === v || (v.length >= 3 && l.includes(v)) || (l.length >= 3 && v.includes(l)) || prochesMots(l, v));
  }
  const n = Number(raw);
  const m = /-?\d+(?:[.,]\d+)?/.exec(valeur.replace(/\s/g, ''));
  if (Number.isFinite(n) && m) return Math.abs(Number(m[0].replace(',', '.')) - n) <= 0.01 + Math.abs(n) * 0.005;
  return normaliserLibelle(raw) === v;
}

const VIDES = new Set(['de', 'du', 'des', 'la', 'le', 'les', 'l', 'd', 'a', 'au', 'aux', 'avec', 'et', 'en', 'ou', 'sur', 'un', 'une']);

/** Libellés proches : mêmes mots à l'accord ou à la racine près (« Electrique » / « électricité »). */
function prochesMots(a: string, b: string): boolean {
  const mots = (s: string) => s.split(/[^a-z0-9]+/).filter((m) => m && !VIDES.has(m));
  const ma = mots(a);
  const mb = mots(b);
  if (!ma.length || !mb.length) return false;
  const egal = (x: string, y: string) => x === y || (x.length >= 5 && y.length >= 5 && x.slice(0, 6) === y.slice(0, 6)) || x.replace(/s$/, '') === y.replace(/s$/, '');
  const communs = ma.filter((x) => mb.some((y) => egal(x, y))).length;
  return communs / Math.min(ma.length, mb.length) >= 0.75 && communs / Math.max(ma.length, mb.length) >= 0.5;
}

/** Cible concrète d'un champ candidat (`gen:`, `em:`, `cg:`…) pour un objet. */
function cibles(objet: Element | null, kind: string | null, candidat: string, doc: Document): { el: Element; rel: string }[] {
  const [prefixe, rel] = candidat.includes(':') ? candidat.split(':') as [string, string] : ['', candidat];
  if (prefixe === 'cg' || prefixe === 'meteo') {
    const el = resolvePath(doc.documentElement, GENERAL[prefixe].split('/').slice(1).join('/'));
    return el ? [{ el, rel }] : [];
  }
  if (!objet) return [];
  if (!prefixe) return [{ el: objet, rel }];
  const sousKind = SOUS_OBJETS[kind ?? '']?.[prefixe as 'gen' | 'em'];
  if (!sousKind) return [];
  const coll = childElements(objet).find((c) => c.localName === `${sousKind}_collection`);
  return (coll ? childElements(coll) : []).filter((c) => c.localName === sousKind).map((el) => ({ el, rel }));
}

export function indexerSources(doc: Document, schema: SchemaHandle | null): IndexSources {
  const out: IndexSources = { champs: new Map(), fiches: new Map(), rattachees: new Set() };
  const coll = resolvePath(doc.documentElement, 'fiche_technique_collection');
  const fiches = (coll ? childElements(coll) : []).filter((e) => e.localName === 'fiche_technique');
  const parCategorie = new Map<string, Element[]>();
  for (const f of fiches) {
    const c = textOf(f, 'enum_categorie_fiche_technique_id') ?? '';
    parCategorie.set(c, [...(parCategorie.get(c) ?? []), f]);
  }

  const rattacher = (fiche: Element, objet: Element | null, kind: string | null, categorie: string) => {
    const table = LIBELLES[categorie] ?? {};
    for (const l of lignes(fiche)) {
      let candidats = table[l.cle] ?? [];
      // ligne créée par l'éditeur : libellé du champ lui-même (objet ou sous-objets)
      if (!candidats.length && objet && kind) {
        const champ = (chemin: string) => schema?.def(chemin + '/donnee_entree')?.children.find((c) => normaliserLibelle(fieldMeta(c.name, c).label) === l.cle);
        const k = KIND_BY_KEY.get(kind)!;
        const direct = champ(k.path);
        if (direct) candidats = [DE + direct.name];
        else for (const [p, sk] of Object.entries(SOUS_OBJETS[kind] ?? {})) {
          const c = champ(KIND_BY_KEY.get(sk)!.path);
          if (c) { candidats = [`${p}:${DE}${c.name}`]; break; }
        }
      }
      if (!candidats.length && categorie === '11') {
        for (const [p, chemin] of Object.entries(GENERAL)) {
          const champ = schema?.def(chemin)?.children.find((c) => normaliserLibelle(fieldMeta(c.name, c).label) === l.cle);
          if (champ) { candidats = [`${p}:${champ.name}`]; break; }
        }
      }
      // seulement vers une donnée renseignée, pas encore documentée
      const possibles = candidats.flatMap((c) => cibles(objet, kind, c, doc))
        .filter((t) => !out.champs.has(cleSource(getUid(t.el) ?? '', t.rel)) && !!resolvePath(t.el, t.rel)?.textContent?.trim());
      // la valeur départage les candidats (surface totale ou opaque, générateur n° 1 ou 2…)
      const cible = possibles.find((t) => concorde(l.valeur, resolvePath(t.el, t.rel), schema))
        ?? (new Set(possibles.map((t) => t.el)).size === 1 ? possibles[0] : undefined);
      if (!cible) continue;
      const u = getUid(cible.el);
      if (!u) continue;
      out.champs.set(cleSource(u, cible.rel), { ligneUid: l.uid, ficheUid: getUid(fiche) ?? '', libelle: l.libelle, valeur: l.valeur, origine: l.origine });
      out.rattachees.add(l.uid);
    }
  };

  for (const [categorie, kindKey] of Object.entries(CATEGORIES)) {
    const kind = KIND_BY_KEY.get(kindKey)!;
    const objets = objectsOfKind(doc, kind);
    const fs = parCategorie.get(categorie) ?? [];
    for (const [f, o] of apparier(fs, objets, categorie, kindKey, doc, schema)) {
      out.fiches.set(getUid(o) ?? '', getUid(f) ?? '');
      rattacher(f, o, kindKey, categorie);
    }
  }
  for (const f of parCategorie.get('11') ?? []) {
    rattacher(f, null, null, '11');
    for (const p of Object.values(GENERAL)) {
      const el = resolvePath(doc.documentElement, p.split('/').slice(1).join('/'));
      const u = el ? getUid(el) : null;
      if (u && !out.fiches.has(u)) out.fiches.set(u, getUid(f) ?? '');
    }
  }
  return out;
}

/** Fiche → objet : meilleure concordance des valeurs, puis rang. */
function apparier(fiches: Element[], objets: Element[], categorie: string, kind: string, doc: Document, schema: SchemaHandle | null): [Element, Element][] {
  const table = LIBELLES[categorie] ?? {};
  const score = (f: Element, o: Element) => lignes(f).reduce((n, l) =>
    n + ((table[l.cle] ?? []).some((c) => cibles(o, kind, c, doc).some((t) => concorde(l.valeur, resolvePath(t.el, t.rel), schema))) ? 1 : 0), 0);
  const paires = fiches.flatMap((f, i) => objets.map((o, j) => ({ f, o, s: score(f, o), d: Math.abs(i - j) })))
    .sort((a, b) => b.s - a.s || a.d - b.d);
  const prisF = new Set<Element>();
  const prisO = new Set<Element>();
  const out: [Element, Element][] = [];
  for (const p of paires) {
    if (prisF.has(p.f) || prisO.has(p.o)) continue;
    // sans aucune concordance, on n'apparie au rang que si les effectifs sont égaux
    if (p.s === 0 && (fiches.length !== objets.length || p.d !== 0)) continue;
    prisF.add(p.f);
    prisO.add(p.o);
    out.push([p.f, p.o]);
  }
  return out;
}

/** Catégorie de fiche technique d'un objet (sous-objets : celle de leur installation). */
export function categorieDe(el: Element): { categorie: string; porteur: Element } | null {
  const k = KIND_BY_KEY.get(schemaKind(el) ?? '');
  if (k && CATEGORIE_DE_KIND[k.key]) return { categorie: CATEGORIE_DE_KIND[k.key], porteur: el };
  if (k?.parentKind && CATEGORIE_DE_KIND[k.parentKind]) {
    let p: Element | null = el.parentElement;
    while (p && schemaKind(p) !== k.parentKind) p = p.parentElement;
    return p ? { categorie: CATEGORIE_DE_KIND[k.parentKind], porteur: p } : null;
  }
  const chemin = schemaPath(el);
  if (Object.values(GENERAL).includes(chemin)) return { categorie: '11', porteur: el };
  return null;
}

function schemaKind(el: Element): string | null {
  const p = schemaPath(el);
  for (const [key, k] of KIND_BY_KEY) if (k.path === p) return key;
  return null;
}

/** L'origine est-elle documentable pour ce champ (objet d'une catégorie, donnée saisie) ? */
export function origineApplicable(d: Dossier, uid: string, rel: string): boolean {
  const el = d.index().get(uid);
  if (!el || !categorieDe(el)) return false;
  const general = Object.values(GENERAL).includes(schemaPath(el));
  return general ? !rel.includes('/') : rel.startsWith(DE) && !/^donnee_entree\/(reference|description|reference_.*)$/.test(rel);
}

/**
 * Fixe l'origine d'une donnée : met à jour sa ligne de fiche technique, ou
 * la crée (et la fiche du composant si besoin) au format des logiciels
 * (« Libellé: valeur »). Une seule modification, annulable. `code` null :
 * retire la ligne créée pour ce champ.
 */
export function definirOrigine(d: Dossier, index: IndexSources, uid: string, rel: string, code: string | null, libelle: string, valeur: string | null): void {
  const el = d.index().get(uid);
  if (!el) throw new Error('Objet introuvable.');
  const existante = index.champs.get(cleSource(uid, rel));
  const origine = ORIGINES.find((o) => o.code === code);
  d.transact(code ? `Origine : ${origine?.label ?? code}` : 'Origine retirée', () => {
    if (existante) {
      const ligne = d.index().get(existante.ligneUid);
      if (!ligne) throw new Error('Ligne de fiche technique introuvable.');
      if (code === null) {
        const o = Array.from(ligne.children).find((c) => c.localName === 'enum_origine_donnee_id');
        o?.remove();
      } else setLeafText(ensurePath(ligne, 'enum_origine_donnee_id', d.schema, d.counter), code);
      return { result: undefined, affectsResults: [] };
    }
    if (code === null) return { result: undefined, affectsResults: [] };
    const cat = categorieDe(el);
    if (!cat) throw new Error('Pas de fiche technique pour ce type de donnée.');
    const porteurUid = getUid(cat.porteur) ?? '';
    let fiche = index.fiches.get(porteurUid) ? d.index().get(index.fiches.get(porteurUid)!) ?? null : null;
    if (!fiche) {
      const kind = KIND_BY_KEY.get('fiche_technique')!;
      const coll = collectionFor(kind, d.working, null, d.schema, d.counter);
      fiche = createElement(coll, 'fiche_technique');
      coll.appendChild(fiche);
      annotate(fiche, d.counter, () => true);
      setLeafText(ensurePath(fiche, 'enum_categorie_fiche_technique_id', d.schema, d.counter), cat.categorie);
    }
    const kindLigne = KIND_BY_KEY.get('sous_fiche_technique')!;
    const coll = collectionFor(kindLigne, d.working, fiche, d.schema, d.counter);
    const ligne = createElement(coll, 'sous_fiche_technique');
    coll.appendChild(ligne);
    annotate(ligne, d.counter, () => true);
    setLeafText(ensurePath(ligne, 'description', d.schema, d.counter), valeur ? `${libelle}: ${valeur}` : libelle);
    if (valeur) setLeafText(ensurePath(ligne, 'valeur', d.schema, d.counter), valeur);
    setLeafText(ensurePath(ligne, 'enum_origine_donnee_id', d.schema, d.counter), code);
    return { result: undefined, affectsResults: [] };
  });
}
