import { Dossier } from '../state/dossier';
import { textOf } from '../edition/doc-ops';
import { RelationGraph } from './relations';

/**
 * Maquette volumique approximative du bien, pour donner un ordre d'idée.
 *
 * Le DPE ne décrit pas la géométrie : seulement des surfaces, des orientations
 * et des rattachements. On en déduit un parallélépipède :
 *  - hauteur = hauteur sous plafond × nombre de niveaux (de l'immeuble si le
 *    DPE porte sur l'immeuble, du logement sinon) ;
 *  - longueur de chaque façade = surface des murs de cette orientation /
 *    hauteur ; largeur = moyenne nord/sud, profondeur = moyenne est/ouest ;
 *    à défaut, déduites de la surface des planchers bas ;
 *  - chaque mur occupe une bande de sa façade proportionnelle à sa surface ;
 *  - baies et portes sont posées sur leur paroi support (ou sur la façade de
 *    leur orientation), en motifs de taille courante dont la surface totale
 *    reprend celle déclarée.
 * Rien n'est écrit dans le dossier : c'est une vue.
 */
export type FacadeKey = 'sud' | 'nord' | 'est' | 'ouest';
export type Adjacence = 'exterieur' | 'lnc' | 'ets' | 'sol' | 'autre';

export interface Segment {
  uid: string;
  title: string;
  surface: number;
  start: number;
  length: number;
  adjacence: Adjacence;
}

export interface Opening {
  uid: string;
  kind: 'baie' | 'porte';
  title: string;
  surface: number;
  facade: FacadeKey | 'toit';
  /** position le long de la façade (m, depuis son début), hauteur du bas */
  x: number;
  y: number;
  w: number;
  h: number;
}

export interface Slab {
  uid: string;
  title: string;
  surface: number;
  start: number;
  length: number;
  adjacence: Adjacence;
}

export interface Maquette {
  width: number;
  depth: number;
  height: number;
  levels: number;
  levelHeight: number;
  facades: Record<FacadeKey, Segment[]>;
  floors: Slab[];
  roofs: Slab[];
  openings: Opening[];
  notes: string[];
  scope: 'immeuble' | 'logement';
}

const ORIENT: Record<string, FacadeKey | 'horizontal'> = { '1': 'sud', '2': 'nord', '3': 'est', '4': 'ouest', '5': 'horizontal' };
export const FACADES: FacadeKey[] = ['sud', 'est', 'nord', 'ouest'];
const MAX_OPENINGS = 600;

function adjacence(code: string | null): Adjacence {
  const c = Number(code);
  if (c === 1) return 'exterieur';
  if (c === 10) return 'ets';
  if ([2, 3, 5].includes(c)) return 'sol';
  if ([4, 20, 22].includes(c)) return 'autre';
  return c ? 'lnc' : 'exterieur';
}

const num = (v: string | null) => (v !== null && Number.isFinite(Number(v)) ? Number(v) : 0);

export function buildMaquette(d: Dossier, graph: RelationGraph = new RelationGraph(d.working)): Maquette | null {
  const root = d.working.documentElement;
  const cg = 'logement/caracteristique_generale';
  const scope: 'immeuble' | 'logement' = /immeuble/.test(d.format.libelleMethodeApplication ?? '') ? 'immeuble' : 'logement';
  const levelHeight = num(textOf(root, `${cg}/hsp`)) || 2.5;
  const levels = Math.max(1, Math.round(num(textOf(root, `${cg}/${scope === 'immeuble' ? 'nombre_niveau_immeuble' : 'nombre_niveau_logement'}`)) || 1));
  const height = levelHeight * levels;
  const notes: string[] = [];

  const of = (kind: string) => graph.objects.filter((o) => o.kind.key === kind);
  const murs = of('mur');
  const openingsOf = (uid: string) => (graph.incoming.get(uid) ?? []).filter((l) => l.ref.key === 'paroi').map((l) => l.from);
  const openingSurface = (el: Element) => num(textOf(el, 'donnee_entree/surface_totale_baie')) || num(textOf(el, 'donnee_entree/surface_porte'));

  // surfaces de façade par orientation
  const wallArea = new Map<string, number>();
  const byFacade: Record<FacadeKey, typeof murs> = { sud: [], nord: [], est: [], ouest: [] };
  for (const m of murs) {
    const f = ORIENT[textOf(m.el, 'donnee_entree/enum_orientation_id') ?? ''];
    const area = num(textOf(m.el, 'donnee_entree/surface_paroi_totale')) ||
      num(textOf(m.el, 'donnee_entree/surface_paroi_opaque')) + openingsOf(m.uid).reduce((n, e) => n + openingSurface(e), 0);
    wallArea.set(m.uid, area);
    if (f && f !== 'horizontal') byFacade[f].push(m);
  }
  const facadeLen = (f: FacadeKey) => byFacade[f].reduce((n, m) => n + (wallArea.get(m.uid) ?? 0), 0) / height;
  const mean = (a: number, b: number) => (a && b ? (a + b) / 2 : a || b);
  const footprint = of('plancher_bas').reduce((n, p) => n + num(textOf(p.el, 'donnee_entree/surface_paroi_opaque')), 0) ||
    of('plancher_haut').reduce((n, p) => n + num(textOf(p.el, 'donnee_entree/surface_paroi_opaque')), 0) ||
    num(textOf(root, `${cg}/${scope === 'immeuble' ? 'surface_habitable_immeuble' : 'surface_habitable_logement'}`)) / levels;
  let width = mean(facadeLen('nord'), facadeLen('sud'));
  let depth = mean(facadeLen('est'), facadeLen('ouest'));
  if (!width && !depth) {
    width = depth = Math.sqrt(footprint || 100);
    notes.push('Aucun mur orienté : volume déduit de la surface des planchers.');
  } else if (!width) {
    width = footprint ? footprint / depth : depth;
    notes.push('Pas de mur au nord ni au sud : largeur déduite de la surface des planchers.');
  } else if (!depth) {
    depth = footprint ? footprint / width : width;
    notes.push('Pas de mur à l\'est ni à l\'ouest : profondeur déduite de la surface des planchers.');
  }
  if (murs.length === 0 && !footprint) return null;
  if (footprint && Math.abs(width * depth - footprint) / footprint > 0.5) {
    notes.push(`L'emprise déduite des murs (${Math.round(width * depth)} m²) diffère de la surface des planchers bas (${Math.round(footprint)} m²) : les proportions sont indicatives.`);
  }
  const sideLength = (f: FacadeKey) => (f === 'sud' || f === 'nord' ? width : depth);

  // bandes de façade
  const facades = {} as Record<FacadeKey, Segment[]>;
  for (const f of FACADES) {
    const total = byFacade[f].reduce((n, m) => n + (wallArea.get(m.uid) ?? 0), 0) || 1;
    let x = 0;
    facades[f] = byFacade[f].map((m) => {
      const length = ((wallArea.get(m.uid) ?? 0) / total) * sideLength(f);
      const seg: Segment = {
        uid: m.uid, title: name(m.el, 'Mur'), surface: wallArea.get(m.uid) ?? 0, start: x, length,
        adjacence: adjacence(textOf(m.el, 'donnee_entree/enum_type_adjacence_id')),
      };
      x += length;
      return seg;
    });
  }

  // ouvertures, regroupées par bande support
  const openings: Opening[] = [];
  const hosts = new Map<string, { facade: FacadeKey | 'toit'; start: number; length: number; items: Element[] }>();
  const host = (key: string, facade: FacadeKey | 'toit', start: number, length: number) => {
    if (!hosts.has(key)) hosts.set(key, { facade, start, length, items: [] });
    return hosts.get(key)!;
  };
  for (const kind of ['baie_vitree', 'porte']) {
    for (const o of of(kind)) {
      const link = (graph.outgoing.get(o.uid) ?? []).find((l) => l.ref.key === 'paroi' && l.status === 'ok');
      const target = link ? graph.objects.find((x) => x.el === link.targets[0]) : undefined;
      if (target?.kind.key === 'mur') {
        const f = FACADES.find((k) => facades[k].some((s) => s.uid === target.uid));
        const seg = f ? facades[f].find((s) => s.uid === target.uid)! : null;
        if (f && seg) {
          host(seg.uid, f, seg.start, seg.length).items.push(o.el);
          continue;
        }
      }
      const orient = ORIENT[textOf(o.el, 'donnee_entree/enum_orientation_id') ?? ''];
      const roof = target?.kind.key === 'plancher_haut' || orient === 'horizontal' || textOf(o.el, 'donnee_entree/enum_inclinaison_vitrage_id') === '4';
      if (roof) host('toit', 'toit', 0, width).items.push(o.el);
      else if (orient) host('f:' + orient, orient, 0, sideLength(orient)).items.push(o.el);
    }
  }
  for (const h of hosts.values()) placeOpenings(h, levels, levelHeight, depth, openings);
  if (openings.length >= MAX_OPENINGS) notes.push('Ouvertures très nombreuses : seule une partie est dessinée.');

  const slabs = (kind: string, fallback: string): Slab[] => {
    const list = of(kind);
    const total = list.reduce((n, p) => n + num(textOf(p.el, 'donnee_entree/surface_paroi_opaque')), 0) || 1;
    let x = 0;
    return list.map((p) => {
      const s = num(textOf(p.el, 'donnee_entree/surface_paroi_opaque'));
      const length = (s / total) * width;
      const slab = { uid: p.uid, title: name(p.el, fallback), surface: s, start: x, length, adjacence: adjacence(textOf(p.el, 'donnee_entree/enum_type_adjacence_id')) };
      x += length;
      return slab;
    });
  };

  return {
    width, depth, height, levels, levelHeight, facades, openings, notes, scope,
    floors: slabs('plancher_bas', 'Plancher bas'),
    roofs: slabs('plancher_haut', 'Plancher haut'),
  };

  function name(el: Element, fallback: string): string {
    return textOf(el, 'donnee_entree/description') ?? fallback;
  }

  function placeOpenings(h: { facade: FacadeKey | 'toit'; start: number; length: number; items: Element[] }, lv: number, lh: number, roofDepth: number, out: Opening[]): void {
    const total = h.items.reduce((n, e) => n + openingSurface(e), 0);
    if (!total || h.length <= 0) return;
    let x = h.start;
    for (const el of h.items) {
      const isDoor = el.localName === 'porte';
      const surface = openingSurface(el);
      const span = (surface / total) * h.length;
      const unitH = isDoor ? Math.min(2.1, lh * 0.85) : h.facade === 'toit' ? 1.2 : Math.min(1.45, lh * 0.6);
      const maxW = isDoor ? 1.6 : 2.4;
      const declared = Math.max(1, Math.round(num(textOf(el, isDoor ? 'donnee_entree/nb_porte' : 'donnee_entree/nb_baie')) || 1));
      let units = Math.max(declared, Math.ceil(surface / (unitH * maxW)));
      units = Math.min(units, MAX_OPENINGS - out.length);
      if (units <= 0) return;
      const rows = h.facade === 'toit' ? Math.max(1, Math.round(Math.sqrt(units * roofDepth / Math.max(span, 0.1)))) : isDoor ? 1 : Math.min(lv, units);
      const perRow = Math.ceil(units / rows);
      const unitW = Math.min(surface / units / unitH, (span / perRow) * 0.8);
      const gap = (span - perRow * unitW) / perRow;
      for (let i = 0; i < units; i++) {
        const r = Math.floor(i / perRow);
        const c = i % perRow;
        const y = h.facade === 'toit' ? (r + 0.5) * (roofDepth / rows) - unitH / 2 : isDoor ? 0 : r * lh + Math.min(0.9, lh * 0.35);
        out.push({ uid: getUidOf(el), kind: isDoor ? 'porte' : 'baie', title: name(el, isDoor ? 'Porte' : 'Baie'), surface, facade: h.facade, x: x + gap / 2 + c * (unitW + gap), y, w: unitW, h: unitH });
      }
      x += span;
    }
  }

  function getUidOf(el: Element): string {
    return graph.objects.find((o) => o.el === el)?.uid ?? '';
  }
}
