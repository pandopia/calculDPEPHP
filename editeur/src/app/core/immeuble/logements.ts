import { KIND_BY_KEY } from '../metier/catalog';
import { SchemaHandle } from '../schema/schema-registry';
import { objectsOfKind, referenceValue, textOf } from '../edition/doc-ops';
import { childElements, parseXml, resolvePath } from '../xml/safe-xml';
import { getUid } from '../xml/uid';

/**
 * Génération des DPE des appartements à partir du DPE de l'immeuble
 * (méthode 3CL-2021, §17.2.2, p. 114-119).
 *
 * Le diagnostiqueur décrit les logements de l'immeuble (référence, surface
 * habitable, position) et relie chacun aux parois sur lesquelles il donne :
 * « à chacun de ces différents types de parois est associée la surface
 * habitable totale des appartements concernés » (§17.2.2.2.2). Ces liaisons
 * servent au besoin de chauffage simplifié de chaque appartement, donc à sa
 * clé de répartition (chauffage individuel, ou collectif avec
 * individualisation des frais). Le calcul lui-même est fait par le moteur
 * (CalculDpePHP::calculateBuilding) : l'éditeur ne prépare que ses entrées.
 *
 * Le XSD ADEME ne prévoit aucun emplacement pour ces liaisons : elles sont
 * conservées dans le brouillon, jamais écrites dans le XML exporté.
 */

export type TypeLiaison = 'murs' | 'plancher' | 'plafond' | 'fenetre' | 'porte' | 'pont_thermique';

export interface TypeLiaisonDef {
  key: TypeLiaison;
  kind: string;
  label: string;
  /** surface (m²) ou, pour un pont thermique, longueur (m) */
  surface: string;
  unite: string;
}

/** Clés du moteur (ApartmentInput::$associations) et objets XML correspondants. */
export const TYPES_LIAISON: TypeLiaisonDef[] = [
  { key: 'murs', kind: 'mur', label: 'Murs', surface: 'donnee_entree/surface_paroi_opaque', unite: 'm²' },
  { key: 'plancher', kind: 'plancher_bas', label: 'Planchers bas', surface: 'donnee_entree/surface_paroi_opaque', unite: 'm²' },
  { key: 'plafond', kind: 'plancher_haut', label: 'Planchers hauts', surface: 'donnee_entree/surface_paroi_opaque', unite: 'm²' },
  { key: 'fenetre', kind: 'baie_vitree', label: 'Baies vitrées', surface: 'donnee_entree/surface_totale_baie', unite: 'm²' },
  { key: 'porte', kind: 'porte', label: 'Portes', surface: 'donnee_entree/surface_porte', unite: 'm²' },
  { key: 'pont_thermique', kind: 'pont_thermique', label: 'Ponts thermiques', surface: 'donnee_entree/l', unite: 'm' },
];

export const UNITE: Record<TypeLiaison, string> = Object.fromEntries(TYPES_LIAISON.map((t) => [t.key, t.unite])) as Record<TypeLiaison, string>;

/** Libellés XSD de `logement_visite` (enum_position_etage_logement_id, enum_typologie_logement_id). */
export const POSITIONS: Record<number, string> = { 1: 'Rez-de-chaussée', 2: 'Étage intermédiaire', 3: 'Dernier étage' };
export const TYPOLOGIES: Record<number, string> = { 1: 'T1', 2: 'T2', 3: 'T3', 4: 'T4', 5: 'T5', 6: 'T6', 7: 'T7 ou plus' };

export interface Logement {
  /** identifiant interne stable (la référence, elle, se modifie) */
  id: string;
  reference: string;
  description: string;
  surface: number | null;
  position: number | null;
  typologie: number | null;
  visite: boolean;
  liaisons: Record<TypeLiaison, string[]>;
}

export interface ConfigImmeuble {
  logements: Logement[];
  /** chauffage collectif : 1 = avec individualisation des frais (IFC), 2 = sans */
  repartitionChauffage: 1 | 2 | null;
  /** coefficient d'individualisation des frais de chauffage (vide : 0,7 par défaut) */
  coefIfc: number | null;
  /** parois sans liaison : laisser le moteur les répartir par approximation documentée */
  approximerLiaisons: boolean;
}

export function configVide(): ConfigImmeuble {
  return { logements: [], repartitionChauffage: null, coefIfc: null, approximerLiaisons: false };
}

export function liaisonsVides(): Record<TypeLiaison, string[]> {
  return { murs: [], plancher: [], plafond: [], fenetre: [], porte: [], pont_thermique: [] };
}

/** Brouillons antérieurs : types de liaison ajoutés depuis complétés à vide. */
export function normaliser(cfg: ConfigImmeuble): ConfigImmeuble {
  for (const l of cfg.logements) l.liaisons = { ...liaisonsVides(), ...l.liaisons };
  return cfg;
}

// ---------------------------------------------------------------- immeuble

export type Systeme = 'individuel' | 'collectif' | 'mixte';

export interface ContexteImmeuble {
  methode: string | null;
  estImmeuble: boolean;
  chauffage: Systeme | null;
  ecs: Systeme | null;
  nombreAppartements: number | null;
  surfaceImmeuble: number | null;
  /** motif d'impossibilité de générer les DPE logements, sinon null */
  nonSupporte: string | null;
}

const IMMEUBLE: Record<string, [Systeme, Systeme]> = {
  '6': ['individuel', 'individuel'], '7': ['collectif', 'individuel'], '8': ['individuel', 'collectif'], '9': ['collectif', 'collectif'],
  '26': ['mixte', 'mixte'], '27': ['mixte', 'individuel'], '28': ['mixte', 'collectif'], '29': ['individuel', 'mixte'], '30': ['collectif', 'mixte'],
};

export function contexteImmeuble(doc: Document): ContexteImmeuble {
  const cg = resolvePath(doc.documentElement, 'logement/caracteristique_generale');
  const methode = textOf(cg, 'enum_methode_application_dpe_log_id');
  const sys = methode ? IMMEUBLE[methode] : undefined;
  const nb = numberOrNull(textOf(cg, 'nombre_appartement'));
  const ctx: ContexteImmeuble = {
    methode,
    estImmeuble: !!sys,
    chauffage: sys?.[0] ?? null,
    ecs: sys?.[1] ?? null,
    nombreAppartements: nb === null ? null : Math.round(nb),
    surfaceImmeuble: numberOrNull(textOf(cg, 'surface_habitable_immeuble')),
    nonSupporte: null,
  };
  if (!sys) ctx.nonSupporte = 'Le dossier n\'est pas un DPE immeuble collectif : la génération des DPE logements part d\'un DPE immeuble (§17.2.2).';
  else if (sys.includes('mixte')) {
    ctx.nonSupporte = 'Chauffage ou ECS mixte (collectif-individuel) : répartition hétérogène non encore prise en charge par le moteur (§17.2.2.2.3 et §17.2.2.3.2).';
  }
  return ctx;
}

/** Codes du moteur : chauffage 1 = collectif avec IFC, 2 = collectif sans IFC, 3 = individuel ; ECS 0 = individuelle, 1 = collective. */
export function repartition(cfg: ConfigImmeuble, ctx: ContexteImmeuble): { chauffage: 1 | 2 | 3 | null; ecs: 0 | 1 | null } {
  const chauffage = ctx.chauffage === 'individuel' ? 3 : ctx.chauffage === 'collectif' ? cfg.repartitionChauffage : null;
  const ecs = ctx.ecs === 'individuel' ? 0 : ctx.ecs === 'collectif' ? 1 : null;
  return { chauffage, ecs };
}

/**
 * §17.2.2.2 : les liaisons ne servent qu'à la clé fondée sur le besoin de
 * chauffage (méthode 2). Chauffage collectif sans IFC (méthode 1), ou IFC nul :
 * répartition au prorata des surfaces, liaisons inutiles.
 */
export function liaisonsUtiles(cfg: ConfigImmeuble, ctx: ContexteImmeuble): boolean {
  const ch = repartition(cfg, ctx).chauffage;
  return ch === 3 || (ch === 1 && (cfg.coefIfc ?? 0.7) > 0);
}

// ---------------------------------------------------------------- parois

export interface Paroi {
  type: TypeLiaison;
  uid: string | null;
  reference: string | null;
  nom: string;
  surface: number | null;
  orientation: string | null;
  /** reference_paroi : mur ou plancher support d'une baie ou d'une porte */
  support: string | null;
  /**
   * Pont thermique rattaché à ses parois par reference_1/reference_2 : il
   * suit alors les logements de ces parois, une liaison directe est facultative
   * (elle prime si elle existe).
   */
  suitParois: boolean;
}

export function parois(doc: Document, schema: SchemaHandle | null): Paroi[] {
  const out: Paroi[] = [];
  /** pont thermique → reference_2 et type de liaison */
  const ponts = new Map<Paroi, { r2: string | null; liaison: string | null }>();
  for (const t of TYPES_LIAISON) {
    const kind = KIND_BY_KEY.get(t.kind)!;
    const orientations = schema?.def(`${kind.path}/donnee_entree/enum_orientation_id`)?.enumLabels ?? {};
    objectsOfKind(doc, kind).forEach((el, i) => {
      const o = textOf(el, 'donnee_entree/enum_orientation_id');
      const pont = t.key === 'pont_thermique';
      const p: Paroi = {
        type: t.key,
        uid: getUid(el),
        reference: referenceValue(el, kind),
        nom: textOf(el, 'donnee_entree/description') ?? `${kind.label} ${i + 1}`,
        surface: numberOrNull(textOf(el, t.surface)),
        orientation: o ? (orientations[o] ?? o) : null,
        support: textOf(el, pont ? 'donnee_entree/reference_1' : 'donnee_entree/reference_paroi'),
        suitParois: false,
      };
      out.push(p);
      if (pont) ponts.set(p, { r2: textOf(el, 'donnee_entree/reference_2'), liaison: textOf(el, 'donnee_entree/enum_type_liaison_id') });
    });
  }
  // même règle que le moteur : les deux références sur des parois décrites,
  // ou une seule pour un plancher intermédiaire ou un refend (types 2 et 4)
  const refs = new Set(out.filter((p) => p.type !== 'pont_thermique' && p.reference).map((p) => p.reference!));
  for (const [p, { r2, liaison }] of ponts) {
    const ok1 = !!p.support && refs.has(p.support);
    const ok2 = !!r2 && refs.has(r2);
    p.suitParois = (ok1 && ok2) || ((liaison === '2' || liaison === '4') && (ok1 || ok2));
  }
  return out;
}

// ---------------------------------------------------------------- contrôles

export interface ControleLogements {
  gravite: 'erreur' | 'avertissement' | 'info';
  message: string;
  logementId?: string;
}

export function controler(cfg: ConfigImmeuble, ctx: ContexteImmeuble, liste: Paroi[]): ControleLogements[] {
  const out: ControleLogements[] = [];
  const err = (message: string, logementId?: string) => out.push({ gravite: 'erreur', message, logementId });
  const warn = (message: string, logementId?: string) => out.push({ gravite: 'avertissement', message, logementId });
  if (ctx.nonSupporte) err(ctx.nonSupporte);
  const n = cfg.logements.length;
  if (!n) {
    err('Aucun logement décrit.');
    return out;
  }
  if (ctx.nombreAppartements === null) err('Nombre d\'appartements de l\'immeuble non renseigné (Caractéristiques générales).');
  else if (ctx.nombreAppartements !== n) {
    err(`${n} logement(s) décrit(s) pour ${ctx.nombreAppartements} appartement(s) déclaré(s) : tous les logements de l'immeuble doivent être décrits.`);
  }
  const somme = cfg.logements.reduce((s, l) => s + (l.surface ?? 0), 0);
  if (ctx.surfaceImmeuble === null) err('Surface habitable de l\'immeuble non renseignée (Caractéristiques générales).');
  else if (Math.abs(somme - ctx.surfaceImmeuble) > 0.01) {
    err(`Somme des surfaces des logements (${fmt(somme)} m²) différente de la surface habitable de l'immeuble (${fmt(ctx.surfaceImmeuble)} m²).`);
  }
  const vus = new Map<string, number>();
  for (const l of cfg.logements) vus.set(l.reference.trim(), (vus.get(l.reference.trim()) ?? 0) + 1);
  for (const l of cfg.logements) {
    const ref = l.reference.trim();
    if (!ref) err('Logement sans référence.', l.id);
    else if (vus.get(ref)! > 1) err(`Référence « ${ref} » utilisée par plusieurs logements.`, l.id);
    if (l.surface === null || !(l.surface > 0)) err(`Logement ${ref || 'sans référence'} : surface habitable manquante.`, l.id);
  }
  const rep = repartition(cfg, ctx);
  if (ctx.chauffage === 'collectif' && rep.chauffage === null) {
    err('Chauffage collectif : indiquer si les frais de chauffage sont individualisés (§17.2.2.1).');
  }
  if (cfg.coefIfc !== null && !(cfg.coefIfc >= 0 && cfg.coefIfc <= 1)) err('Le coefficient d\'individualisation des frais de chauffage doit être compris entre 0 et 1.');

  if (liaisonsUtiles(cfg, ctx)) {
    const refs = new Map<string, Set<string>>();
    for (const l of cfg.logements) for (const t of TYPES_LIAISON) for (const r of l.liaisons[t.key]) {
      refs.set(`${t.key}:${r}`, (refs.get(`${t.key}:${r}`) ?? new Set()).add(l.id));
    }
    const sansRef = liste.filter((p) => !p.reference && !p.suitParois);
    if (sansRef.length) err(`${sansRef.length} paroi(s) sans référence : elles ne peuvent pas être reliées aux logements.`);
    const orphelines = liste.filter((p) => p.reference && !p.suitParois && !refs.has(`${p.type}:${p.reference}`));
    if (orphelines.length) {
      let msg = `${orphelines.length} paroi(s) reliée(s) à aucun logement (${orphelines.slice(0, 4).map((p) => p.nom).join(', ')}${orphelines.length > 4 ? '…' : ''}).`;
      if (orphelines.some((p) => p.type === 'pont_thermique')) {
        msg += ' Un pont thermique sans paroi associée dans le XML (reference_1/reference_2) se relie directement aux logements qu\'il borde.';
      }
      if (cfg.approximerLiaisons) out.push({ gravite: 'info', message: msg + ' Elles seront réparties par approximation (mur support, étage, sinon tous les logements).' });
      else err(msg + ' Reliez-les, ou autorisez l\'approximation.');
    }
    const parRef = new Map(liste.filter((p) => p.reference).map((p) => [`${p.type}:${p.reference}`, p]));
    const isoles = cfg.logements.filter((l) => TYPES_LIAISON.every((t) => !l.liaisons[t.key].length));
    if (isoles.length) {
      warn(`${isoles.length} logement(s) relié(s) à aucune paroi (${isoles.slice(0, 4).map((l) => l.reference || 'sans référence').join(', ')}${isoles.length > 4 ? '…' : ''}).`, isoles[0].id);
    }
    for (const l of cfg.logements) {
      for (const t of TYPES_LIAISON) for (const r of l.liaisons[t.key]) {
        if (!parRef.has(`${t.key}:${r}`)) warn(`Logement ${l.reference} : ${t.label.toLowerCase()} « ${r} » introuvable dans le dossier (liaison à retirer).`, l.id);
      }
      // une baie ou une porte appartient à sa paroi support
      for (const t of ['fenetre', 'porte'] as const) for (const r of l.liaisons[t]) {
        const p = parRef.get(`${t}:${r}`);
        const sup = p?.support ? liste.find((x) => x.reference === p.support) : undefined;
        if (sup?.reference && !l.liaisons[sup.type].includes(sup.reference)) {
          warn(`Logement ${l.reference} : relié à « ${p!.nom} » sans être relié à sa paroi support « ${sup.nom} ».`, l.id);
        }
      }
    }
    if (rep.chauffage !== null && cfg.logements.some((l) => l.position === null)) {
      out.push({ gravite: 'info', message: 'Position dans l\'immeuble non renseignée pour certains logements : elle sert au pré-remplissage et à l\'approximation des planchers.' });
    }
  } else if (rep.chauffage !== null) {
    out.push({ gravite: 'info', message: 'Chauffage réparti au prorata des surfaces habitables (§17.2.2.2.1) : les liaisons aux parois ne sont pas utilisées.' });
  }
  return out;
}

// ---------------------------------------------------------------- saisie

/** Logements manquants jusqu'au nombre d'appartements déclaré, surface restante répartie à parts égales. */
export function logementsManquants(cfg: ConfigImmeuble, ctx: ContexteImmeuble, newId: () => string): Logement[] {
  const manque = (ctx.nombreAppartements ?? 0) - cfg.logements.length;
  if (manque <= 0) return [];
  const reste = (ctx.surfaceImmeuble ?? 0) - cfg.logements.reduce((s, l) => s + (l.surface ?? 0), 0);
  const part = reste > 0 ? Math.floor((reste / manque) * 100) / 100 : null;
  const pris = new Set(cfg.logements.map((l) => l.reference));
  const out: Logement[] = [];
  for (let i = 0, n = 1; i < manque; i++) {
    while (pris.has(`Lot ${n}`)) n++;
    pris.add(`Lot ${n}`);
    // le dernier prend l'arrondi : la somme égale exactement la surface de l'immeuble
    const surface = part === null ? null : i === manque - 1 ? round2(reste - part * (manque - 1)) : part;
    out.push({ id: newId(), reference: `Lot ${n}`, description: '', surface, position: null, typologie: null, visite: false, liaisons: liaisonsVides() });
  }
  return out;
}

/** Logements visités déclarés dans le XML (dpe_immeuble/logement_visite_collection). */
export function logementsVisites(doc: Document, newId: () => string): Logement[] {
  const coll = resolvePath(doc.documentElement, 'dpe_immeuble/logement_visite_collection');
  return (coll ? childElements(coll) : []).filter((e) => e.localName === 'logement_visite').map((e, i) => {
    const description = textOf(e, 'description') ?? '';
    return {
      id: newId(),
      reference: description.trim().slice(0, 40) || `Visité ${i + 1}`,
      description,
      surface: numberOrNull(textOf(e, 'surface_habitable_logement')),
      position: numberOrNull(textOf(e, 'enum_position_etage_logement_id')),
      typologie: numberOrNull(textOf(e, 'enum_typologie_logement_id')),
      visite: true,
      liaisons: liaisonsVides(),
    };
  });
}

/**
 * Pré-remplissage explicite, jamais appliqué en silence :
 *  - planchers bas → logements du rez-de-chaussée, planchers hauts → du dernier étage ;
 *  - baies et portes → logements reliés à leur paroi support.
 * Les liaisons existantes sont conservées. Retourne le nombre de liaisons ajoutées.
 */
export function preRemplir(cfg: ConfigImmeuble, liste: Paroi[]): number {
  let ajout = 0;
  const add = (l: Logement, t: TypeLiaison, ref: string) => {
    if (!l.liaisons[t].includes(ref)) { l.liaisons[t].push(ref); ajout++; }
  };
  for (const p of liste) {
    if (!p.reference) continue;
    if (p.type === 'plancher') for (const l of cfg.logements) { if (l.position === 1) add(l, 'plancher', p.reference); }
    if (p.type === 'plafond') for (const l of cfg.logements) { if (l.position === 3) add(l, 'plafond', p.reference); }
  }
  for (const p of liste) {
    if ((p.type !== 'fenetre' && p.type !== 'porte') || !p.reference || !p.support) continue;
    const sup = liste.find((x) => x.reference === p.support && x.type !== 'fenetre' && x.type !== 'porte');
    if (!sup?.reference) continue;
    for (const l of cfg.logements) if (l.liaisons[sup.type].includes(sup.reference)) add(l, p.type, p.reference);
  }
  return ajout;
}

/** Retire les liaisons vers des parois qui n'existent plus dans le dossier. */
export function nettoyerLiaisons(cfg: ConfigImmeuble, liste: Paroi[]): number {
  const existe = new Set(liste.filter((p) => p.reference).map((p) => `${p.type}:${p.reference}`));
  let n = 0;
  for (const l of cfg.logements) for (const t of TYPES_LIAISON) {
    const avant = l.liaisons[t.key].length;
    l.liaisons[t.key] = l.liaisons[t.key].filter((r) => existe.has(`${t.key}:${r}`));
    n += avant - l.liaisons[t.key].length;
  }
  return n;
}

// ---------------------------------------------------------------- contrat d'API

/** Corps JSON de POST /api/calculdpe/logements et /api/calculdpe/pdflogement. */
export interface RequeteLogements {
  xml: string;
  repartition: { chauffage: 1 | 2 | 3; ecs: 0 | 1; coefficientIfc: number | null; approximerLiaisons: boolean };
  logements: {
    reference: string;
    description: string;
    surface: number;
    position: number | null;
    typologie: number | null;
    visite: boolean;
    liaisons: Record<TypeLiaison, string[]>;
  }[];
  logement?: string;
}

export function requete(cfg: ConfigImmeuble, ctx: ContexteImmeuble, xml: string, logement?: string): RequeteLogements {
  const rep = repartition(cfg, ctx);
  if (rep.chauffage === null || rep.ecs === null) throw new Error('Répartition du chauffage ou de l\'ECS indéterminée.');
  return {
    xml,
    repartition: { chauffage: rep.chauffage, ecs: rep.ecs, coefficientIfc: rep.chauffage === 1 ? cfg.coefIfc : null, approximerLiaisons: cfg.approximerLiaisons },
    logements: cfg.logements.map((l) => ({
      reference: l.reference.trim(), description: l.description, surface: l.surface ?? 0, position: l.position, typologie: l.typologie,
      visite: l.visite, liaisons: structuredClone(l.liaisons),
    })),
    ...(logement ? { logement } : {}),
  };
}

export interface SyntheseResultat {
  surface: number | null;
  classeEnergie: string | null;
  classeClimat: string | null;
  epM2: number | null;
  gesM2: number | null;
  ep: number | null;
  ef: number | null;
  ges: number | null;
  cout: number | null;
  /** énergie finale par usage, kWh/an */
  usages: { chauffage: number | null; ecs: number | null; refroidissement: number | null; eclairage: number | null; auxiliaires: number | null };
}

export interface ResultatLogement {
  reference: string;
  erreur: string | null;
  synthese: SyntheseResultat | null;
}

export interface ResultatsLogements {
  date: string;
  moteur: string;
  /** empreinte des entrées (XML sans résultats + logements) au moment du calcul */
  empreinte: string;
  batiment: { erreur: string | null; synthese: SyntheseResultat | null };
  logements: ResultatLogement[];
  hypotheses: string[];
}

/** Réponse JSON de POST /api/calculdpe/logements. */
export interface ReponseLogements {
  batiment: { erreur: string | null; xml: string | null; resultats?: Record<string, unknown> | null };
  logements: { reference: string; erreur: string | null; xml: string | null; resultats?: Record<string, unknown> | null }[];
  hypotheses?: string[];
}

export function lireReponse(json: unknown): ReponseLogements {
  const r = json as Partial<ReponseLogements> | null;
  if (!r || typeof r !== 'object' || !r.batiment || !Array.isArray(r.logements)) {
    throw new Error('Réponse du service inattendue : objets « batiment » et « logements » attendus.');
  }
  const doc = (x: { erreur?: unknown; xml?: unknown; resultats?: unknown }) => ({
    erreur: typeof x.erreur === 'string' && x.erreur ? x.erreur : null,
    xml: typeof x.xml === 'string' && x.xml ? x.xml : null,
    resultats: x.resultats && typeof x.resultats === 'object' ? (x.resultats as Record<string, unknown>) : null,
  });
  return {
    batiment: doc(r.batiment),
    logements: r.logements.map((l) => ({ reference: String(l?.reference ?? ''), ...doc(l ?? {}) })),
    hypotheses: Array.isArray(r.hypotheses) ? r.hypotheses.map(String) : [],
  };
}

/** Synthèse lue dans le XML calculé (balises ADEME de `sortie`), sinon dans `resultats`. */
export function synthese(xml: string | null, resultats?: Record<string, unknown> | null): SyntheseResultat | null {
  let get: (path: string) => string | null;
  if (xml) {
    const doc = parseXml(xml);
    const sortie = resolvePath(doc.documentElement, 'logement/sortie');
    if (!sortie) return null;
    const cg = resolvePath(doc.documentElement, 'logement/caracteristique_generale');
    get = (p) => (p === 'surface' ? textOf(cg, 'surface_habitable_logement') ?? textOf(cg, 'surface_habitable_immeuble') : textOf(sortie, p));
  } else if (resultats) {
    get = (p) => {
      const v = resultats[p.split('/').pop()!];
      return v === null || v === undefined ? null : String(v);
    };
  } else return null;
  const n = (p: string) => numberOrNull(get(p));
  return {
    surface: n('surface'),
    classeEnergie: get('ep_conso/classe_bilan_dpe'),
    classeClimat: get('emission_ges/classe_emission_ges'),
    epM2: n('ep_conso/ep_conso_5_usages_m2'),
    gesM2: n('emission_ges/emission_ges_5_usages_m2'),
    ep: n('ep_conso/ep_conso_5_usages'),
    ef: n('ef_conso/conso_5_usages'),
    ges: n('emission_ges/emission_ges_5_usages'),
    cout: n('cout/cout_5_usages'),
    usages: {
      chauffage: n('ef_conso/conso_ch'),
      ecs: n('ef_conso/conso_ecs'),
      refroidissement: n('ef_conso/conso_fr'),
      eclairage: n('ef_conso/conso_eclairage'),
      auxiliaires: n('ef_conso/conso_totale_auxiliaire'),
    },
  };
}

export function resultats(rep: ReponseLogements, empreinteEntrees: string, moteur: string): ResultatsLogements {
  return {
    date: new Date().toISOString(),
    moteur,
    empreinte: empreinteEntrees,
    batiment: { erreur: rep.batiment.erreur, synthese: safeSynthese(rep.batiment.xml, rep.batiment.resultats) },
    logements: rep.logements.map((l) => ({ reference: l.reference, erreur: l.erreur, synthese: l.erreur ? null : safeSynthese(l.xml, l.resultats) })),
    hypotheses: rep.hypotheses ?? [],
  };
}

function safeSynthese(xml: string | null, res?: Record<string, unknown> | null): SyntheseResultat | null {
  try {
    return synthese(xml, res);
  } catch {
    return synthese(null, res);
  }
}

/**
 * Empreinte des entrées du calcul : XML sans `donnee_intermediaire` ni
 * `sortie`, et logements. Des résultats d'une autre empreinte sont périmés.
 */
export function empreinte(xml: string, cfg: ConfigImmeuble): string {
  const sansResultats = xml.replace(/<(donnee_intermediaire|sortie)(\s[^>]*)?>[\s\S]*?<\/\1>|<(donnee_intermediaire|sortie)(\s[^>]*)?\/>/g, '');
  const logements = cfg.logements.map(({ id: _id, ...l }) => l);
  return fnv1a(sansResultats + JSON.stringify([logements, cfg.repartitionChauffage, cfg.coefIfc, cfg.approximerLiaisons]));
}

function fnv1a(s: string): string {
  let h = 0x811c9dc5;
  for (let i = 0; i < s.length; i++) {
    h ^= s.charCodeAt(i);
    h = Math.imul(h, 0x01000193);
  }
  return (h >>> 0).toString(16).padStart(8, '0') + s.length.toString(36);
}

function numberOrNull(v: string | null): number | null {
  if (v === null || v.trim() === '') return null;
  const n = Number(v.replace(',', '.'));
  return Number.isFinite(n) ? n : null;
}

function round2(n: number): number {
  return Math.round(n * 100) / 100;
}

function fmt(n: number): string {
  return n.toLocaleString('fr-FR', { maximumFractionDigits: 2 });
}
