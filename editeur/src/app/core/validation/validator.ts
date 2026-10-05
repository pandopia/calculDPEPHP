import { isResultPath, KINDS } from '../metier/catalog';
import { applicability, DISPLAY_RULES } from '../metier/display-rules';
import { fieldMeta } from '../metier/field-meta';
import { RelationGraph } from '../metier/relations';
import { schemaPath } from '../schema/schema-registry';
import { Dossier } from '../state/dossier';
import { childElements, indexedPath, resolvePath } from '../xml/safe-xml';
import { getUid } from '../xml/uid';
import { isNil, kindOf, ownerObject, relativePath, SINGLETON_PATHS, textOf } from '../edition/doc-ops';
import { facetViolations } from '../edition/value-codec';
import { isObservatoireHeader } from '../format/format-detector';
import { issue, Issue } from './issues';

/**
 * Contrôles exécutés en direct sur le document de travail (hors XSD complet,
 * lancé à la demande par xsd-validation.ts).
 */
export function validateDocument(d: Dossier, graph: RelationGraph): Issue[] {
  const out: Issue[] = [];
  const doc = d.working;
  const loc = (el: Element) => {
    const owner = ownerObject(el) ?? doc.documentElement;
    return { uid: getUid(owner), field: owner === el ? undefined : relativePath(owner, el), xmlPath: indexedPath(el) };
  };

  for (const m of d.format.messages) {
    out.push(issue({ niveau: 'format', gravite: d.format.niveauSupport === 'complet' ? 'info' : 'avertissement', message: m, uid: getUid(doc.documentElement) }));
  }

  // --- valeurs, codes, balises hors schéma
  const pruned = d.prunable();
  if (d.schema) {
    const walk = (el: Element) => {
      if (pruned.has(el)) return;
      const def = d.schema!.defFor(el);
      if (!def) {
        if (isObservatoireHeader(el)) return;
        out.push(issue({
          niveau: 'format', gravite: 'avertissement', ...loc(el),
          message: `Balise « ${el.localName} » non prévue par le schéma ${d.schema!.fileName} : conservée telle quelle, mais elle fera échouer la validation XSD.`,
        }));
        return;
      }
      if (def.kind === 'simple' && !isNil(el)) {
        const raw = el.textContent ?? '';
        const label = fieldMeta(el.localName, def).label;
        if (raw.trim() !== '') {
          for (const v of facetViolations(raw, def)) {
            const unknownCode = def.enumLabels && !(raw in def.enumLabels);
            out.push(issue({
              niveau: unknownCode ? 'format' : 'champ', gravite: unknownCode && !def.facets.enumeration && withinRange(raw, def.facets) ? 'avertissement' : 'erreur',
              ...loc(el), message: unknownCode ? `${label} : code « ${raw} » inconnu de la liste officielle de cette version — conservé tel quel.` : `${label} : ${v}`,
            }));
          }
        }
        // valeur vide : ignorée (balise de remplissage, retirée à l'export)
      }
      if (def.kind === 'complex') childElements(el).forEach(walk);
    };
    walk(doc.documentElement);
  }

  // --- complétude selon le XSD (zones de saisie uniquement)
  if (d.schema && d.format.niveauSupport === 'complet') {
    const check = (el: Element) => {
      if (pruned.has(el)) return;
      const def = d.schema!.defFor(el);
      if (!def || def.kind !== 'complex' || isResultPath(def.path)) return;
      // bloc importé sans aucune valeur : ignoré tant que rien n'y est saisi
      if (d.meta.origine === 'import' && el.parentElement && !kindOf(el) && !hasAnyValue(el)) return;
      // une balise vide compte comme absente
      const present = new Set(childElements(el).filter((c) => !pruned.has(c)).map((c) => c.localName));
      const choices = new Map<number, boolean>();
      for (const c of def.children) if (c.choiceGroup !== null && present.has(c.name)) choices.set(c.choiceGroup, true);
      for (const c of def.children) {
        if (c.minOccurs < 1 || present.has(c.name) || isResultPath(c.path)) continue;
        if (c.choiceGroup !== null) continue;
        const kindChild = KINDS.find((k) => k.path === c.path);
        // bloc absent : contrôlé seulement une fois renseigné (la validation XSD reste stricte)
        if (c.kind === 'complex' && !kindChild) continue;
        const owner = ownerObject(el) ?? el;
        const nav = kindChild && !kindChild.parentKind ? { tab: kindChild.tab, section: kindChild.section } : undefined;
        out.push(issue({
          nav,
          niveau: 'completude', gravite: 'avertissement', uid: getUid(owner),
          field: kindChild ? undefined : (owner === el ? c.name : relativePath(owner, el) + '/' + c.name),
          xmlPath: indexedPath(el) + '/' + c.name,
          message: kindChild
            ? `Au moins ${c.minOccurs === 1 ? 'un' : c.minOccurs} « ${kindChild.label.toLowerCase()} » exigé par le schéma.`
            : `${fieldMeta(c.name, c).label} : à renseigner (obligatoire selon le XSD).`,
        }));
      }
      for (const c of def.children) {
        if (c.choiceGroup !== null && !choices.has(c.choiceGroup) && c.minOccurs > 0) {
          const alts = def.children.filter((x) => x.choiceGroup === c.choiceGroup).map((x) => x.name);
          if (alts[0] === c.name) {
            out.push(issue({ niveau: 'completude', gravite: 'avertissement', ...loc(el), message: `Choix obligatoire non renseigné parmi : ${alts.join(', ')}.` }));
          }
        }
      }
      for (const c of childElements(el)) check(c);
    };
    check(doc.documentElement);

    // règles d'affichage : champs attendus selon la méthode choisie
    for (const o of graph.objects) {
      for (const rule of DISPLAY_RULES.filter((r) => r.kinds.includes(o.kind.key))) {
        for (const f of rule.fields) {
          const appl = applicability(o.kind.key, f, (r) => textOf(o.el, r));
          const leaf = resolvePath(o.el, f);
          const v = textOf(o.el, f);
          if (appl.applicable && appl.required && v === null && !(leaf && isNil(leaf))) {
            if (!d.schema.def(o.kind.path + '/' + f)) continue;
            out.push(issue({
              niveau: 'completude', gravite: 'avertissement', uid: o.uid, field: f, source: rule.source,
              message: `${fieldMeta(f.split('/').pop()!, null).label} : attendu pour la méthode choisie (${fieldMeta(rule.control.split('/').pop()!, null).label}).`,
            }));
          } else if (!appl.applicable && v !== null) {
            out.push(issue({
              niveau: 'metier', gravite: 'info', uid: o.uid, field: f, source: rule.source,
              message: `${fieldMeta(f.split('/').pop()!, null).label} : valeur présente alors que le champ n'est pas pertinent pour le choix actuel (conservée ; à retirer si elle n'a plus lieu d'être).`,
            }));
          }
        }
      }
    }
  }

  // --- références
  for (const [ref, list] of graph.duplicateReferences) {
    for (const o of list) {
      out.push(issue({
        niveau: 'references', gravite: 'avertissement', uid: o.uid, field: o.kind.idField,
        message: `Référence « ${ref} » partagée par ${list.length} objets : les liens vers cette référence sont ambigus.`,
      }));
    }
  }
  for (const [, links] of graph.outgoing) {
    for (const l of links) {
      if (l.status === 'ok') continue;
      const msg = {
        non_resolu: l.ref.mode === 'shared'
          ? `${l.ref.label} « ${l.value} » : aucune autre partie de générateur ne porte cette référence.`
          : `${l.ref.label} « ${l.value} » : aucun objet du dossier ne porte cette référence.`,
        type_incompatible: `${l.ref.label} « ${l.value} » désigne un objet d'un type non prévu (attendu : ${l.ref.toKinds.map((k) => KINDS.find((x) => x.key === k)!.label.toLowerCase()).join(', ')}).`,
        ambigu: `${l.ref.label} « ${l.value} » : plusieurs objets compatibles portent cette référence.`,
      }[l.status];
      out.push(issue({
        niveau: 'references', gravite: l.status === 'type_incompatible' ? 'erreur' : l.status === 'non_resolu' ? l.ref.unresolved : 'avertissement',
        uid: l.fromUid, field: l.ref.field, message: msg, source: l.ref.doc,
      }));
    }
  }

  if (d.format.niveauSupport !== 'complet') return out;

  // --- règles métier documentées
  for (const inst of graph.objects.filter((o) => o.kind.key === 'installation_chauffage')) {
    const total = num(textOf(inst.el, 'donnee_entree/surface_chauffee'));
    for (const em of graph.objects.filter((o) => o.kind.key === 'emetteur_chauffage' && inst.el.contains(o.el))) {
      const s = num(textOf(em.el, 'donnee_entree/surface_chauffee'));
      if (total !== null && s !== null && s > total * 1.0001) {
        out.push(issue({
          niveau: 'metier', gravite: 'avertissement', uid: em.uid, field: 'donnee_entree/surface_chauffee',
          message: `Surface chauffée de l'émetteur (${fr(s)} m²) supérieure à celle de l'installation (${fr(total)} m²).`,
          source: 'XSD, emetteur_chauffage/surface_chauffee : « sous ensemble de la surface chauffée par l\'installation ».',
        }));
      }
    }
  }
  for (const o of graph.objects.filter((x) => x.kind.key === 'emetteur_chauffage')) {
    const role = textOf(o.el, 'donnee_entree/enum_lien_generateur_emetteur_id');
    if (role && graph.roleLinks(o).length === 0) {
      out.push(issue({
        niveau: 'metier', gravite: 'avertissement', uid: o.uid, field: 'donnee_entree/enum_lien_generateur_emetteur_id',
        message: 'Aucun générateur de l\'installation ne porte le même rôle (génération principale / appoint) que cet émetteur.',
        source: 'Libellés XSD de enum_lien_generateur_emetteur_id : « émetteur lié à la génération principale / d\'appoint ».',
      }));
    }
  }

  // --- plausibilité (indicatif, non réglementaire)
  const logement = resolvePath(doc.documentElement, 'logement');
  const cg = resolvePath(logement, 'caracteristique_generale');
  if (cg) {
    const cgUid = getUid(cg);
    const hsp = num(textOf(cg, 'hsp'));
    if (hsp !== null && (hsp < 1.8 || hsp > 6)) {
      out.push(issue({ niveau: 'plausibilite', gravite: 'avertissement', uid: cgUid, field: 'hsp', message: `Hauteur sous plafond de ${fr(hsp)} m : valeur inhabituelle (attendu entre 1,8 et 6 m).` }));
    }
    const annee = num(textOf(cg, 'annee_construction'));
    const periode = textOf(cg, 'enum_periode_construction_id');
    const label = periode ? d.schema?.def('dpe/logement/caracteristique_generale/enum_periode_construction_id')?.enumLabels?.[periode] : null;
    if (annee !== null && label) {
      const range = periodRange(label);
      if (range && (annee < range[0] || annee > range[1])) {
        out.push(issue({
          niveau: 'plausibilite', gravite: 'avertissement', uid: cgUid, field: 'annee_construction',
          message: `Année de construction ${annee} hors de la période déclarée « ${label} ».`,
        }));
      }
    }
  }
  const adm = resolvePath(doc.documentElement, 'administratif');
  const visite = textOf(adm, 'date_visite_diagnostiqueur');
  const etab = textOf(adm, 'date_etablissement_dpe');
  if (visite && etab && visite > etab) {
    out.push(issue({ niveau: 'plausibilite', gravite: 'avertissement', uid: getUid(adm!), field: 'date_visite_diagnostiqueur', message: 'Date de visite postérieure à la date d\'établissement.' }));
  }
  for (const paroi of graph.objects.filter((o) => o.kind.key === 'mur')) {
    const totale = num(textOf(paroi.el, 'donnee_entree/surface_paroi_totale'));
    if (totale === null) continue;
    let ouvertures = 0;
    for (const l of graph.incoming.get(paroi.uid) ?? []) {
      if (l.ref.key !== 'paroi') continue;
      const k = kindOf(l.from)?.key;
      const s = num(textOf(l.from, k === 'porte' ? 'donnee_entree/surface_porte' : 'donnee_entree/surface_totale_baie'));
      if (s !== null) ouvertures += s;
    }
    if (ouvertures > totale * 1.001) {
      out.push(issue({
        niveau: 'plausibilite', gravite: 'avertissement', uid: paroi.uid, field: 'donnee_entree/surface_paroi_totale',
        message: `Les ouvertures rattachées totalisent ${fr(ouvertures)} m², plus que la surface totale du mur (${fr(totale)} m²).`,
      }));
    }
  }
  for (const o of graph.objects) {
    for (const f of ['donnee_entree/surface_paroi_opaque', 'donnee_entree/surface_totale_baie', 'donnee_entree/surface_porte']) {
      const s = num(textOf(o.el, f));
      if (s !== null && s <= 0) {
        out.push(issue({ niveau: 'plausibilite', gravite: 'avertissement', uid: o.uid, field: f, message: `${fieldMeta(f.split('/').pop()!, null).label} nulle ou négative.` }));
      }
    }
  }

  // --- résultats
  if (d.meta.resultatsObsoletes) {
    const sortie = resolvePath(logement, 'sortie');
    out.push(issue({
      niveau: 'resultats', gravite: 'info', uid: sortie ? getUid(sortie) : getUid(doc.documentElement),
      message: 'Des données d\'entrée ont changé depuis l\'import : les résultats du fichier source sont à recalculer.',
    }));
  }
  void SINGLETON_PATHS;
  void schemaPath;
  return out;
}

function num(v: string | null): number | null {
  if (v === null) return null;
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

function fr(n: number): string {
  return String(Math.round(n * 100) / 100).replace('.', ',');
}

function withinRange(raw: string, f: { minInclusive?: string; maxInclusive?: string }): boolean {
  const n = Number(raw);
  if (Number.isNaN(n)) return false;
  return (f.minInclusive === undefined || n >= Number(f.minInclusive)) && (f.maxInclusive === undefined || n <= Number(f.maxInclusive));
}

/** « avant 1948 » / « 1948-1974 » / « après 2021 » → bornes. */
function periodRange(label: string): [number, number] | null {
  let m = /avant\s+(\d{4})/.exec(label);
  if (m) return [1000, Number(m[1])];
  m = /après\s+(\d{4})/.exec(label);
  if (m) return [Number(m[1]), 2200];
  m = /(\d{4})\s*-\s*(\d{4})/.exec(label);
  return m ? [Number(m[1]), Number(m[2])] : null;
}

/** Le sous-arbre porte-t-il au moins une valeur (texte non vide ou xsi:nil) ? */
function hasAnyValue(el: Element): boolean {
  if (!el.firstElementChild) return (el.textContent ?? '').trim() !== '' || isNil(el);
  for (let c: Element | null = el.firstElementChild; c; c = c.nextElementSibling) if (hasAnyValue(c)) return true;
  return false;
}
