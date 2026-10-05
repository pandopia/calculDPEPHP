import { Dossier } from '../state/dossier';
import { insertOrdered } from '../edition/doc-ops';
import { childElements, indexedPath, parseXml } from '../xml/safe-xml';
import { semanticDiff } from '../xml/semantic-compare';
import { serialize } from '../xml/serializer';
import { allElements, annotate } from '../xml/uid';
import { MoteurCalculError } from './moteur-calcul';

/**
 * Intègre la réponse d'un moteur de calcul au dossier.
 *
 * Garde-fous :
 *  - la réponse doit être un XML DPE bien formé ;
 *  - ses données d'entrée (tout sauf `donnee_intermediaire` et `sortie`)
 *    doivent être identiques à celles envoyées : un moteur ne modifie jamais
 *    la saisie en silence ; sinon rien n'est intégré ;
 *  - seules les zones de résultats sont remplacées, à leur place, par
 *    correspondance de position (même structure des deux côtés) ;
 *  - l'opération est annulable comme toute modification.
 */
const RESULT_TAGS = new Set(['donnee_intermediaire', 'sortie']);

export interface IntegrationReport {
  remplaces: number;
  ajoutes: number;
  avant: { energie: string | null; climat: string | null };
  apres: { energie: string | null; climat: string | null };
}

export function integrerResultats(d: Dossier, envoye: string, reponse: string, moteur: string): IntegrationReport {
  let calc: XMLDocument;
  try {
    calc = parseXml(reponse);
  } catch (e) {
    throw new MoteurCalculError('Réponse du moteur illisible : ' + (e instanceof Error ? e.message : String(e)));
  }
  if (calc.documentElement.localName !== 'dpe') throw new MoteurCalculError('La réponse du moteur n\'est pas un XML DPE.');

  const entree = stripResults(parseXml(envoye));
  const ecarts = semanticDiff(entree, stripResults(parseXml(reponse)), 5);
  if (ecarts.length) {
    throw new MoteurCalculError(
      `Le moteur a renvoyé des données d'entrée différentes de celles envoyées : résultats non intégrés. Premier écart : ${ecarts[0]}`,
    );
  }

  const label = (doc: Document, tag: string) => doc.getElementsByTagName(tag)[0]?.textContent?.trim() ?? null;
  const avant = { energie: label(d.working, 'classe_bilan_dpe'), climat: label(d.working, 'classe_emission_ges') };

  return d.transact(`Calcul (${moteur})`, () => {
    // éléments du document de travail par chemin positionnel ; les balises vides
    // retirées à l'export ne déplacent pas les objets (jamais vides)
    const byPath = new Map<string, Element>();
    const pruned = d.prunable();
    for (const el of allElements(d.working.documentElement)) if (!pruned.has(el)) byPath.set(prunedPath(el, pruned), el);
    let remplaces = 0;
    let ajoutes = 0;
    for (const res of allElements(calc.documentElement).filter((e) => RESULT_TAGS.has(e.localName) && !hasResultAncestor(e))) {
      const parent = res.parentElement ? byPath.get(indexedPath(res.parentElement)) : undefined;
      if (!parent) continue;
      const imported = d.working.importNode(res, true) as Element;
      const old = childElements(parent).find((c) => c.localName === res.localName);
      if (old) {
        old.replaceWith(imported);
        remplaces++;
      } else {
        insertOrdered(parent, imported, d.schema);
        ajoutes++;
      }
      annotate(imported, d.counter, (el) => d.schema?.defFor(el)?.kind === 'complex');
    }
    d.meta.resultatsObsoletes = false;
    d.meta.motifsObsolescence = [];
    d.meta.calcul = { moteur, date: new Date().toISOString() };
    return {
      result: {
        remplaces, ajoutes, avant,
        apres: { energie: label(d.working, 'classe_bilan_dpe'), climat: label(d.working, 'classe_emission_ges') },
      },
      affectsResults: [],
    };
  });
}

function stripResults(doc: XMLDocument): XMLDocument {
  for (const el of allElements(doc.documentElement).filter((e) => RESULT_TAGS.has(e.localName))) el.remove();
  return doc;
}

function hasResultAncestor(el: Element): boolean {
  for (let p = el.parentElement; p; p = p.parentElement) if (RESULT_TAGS.has(p.localName)) return true;
  return false;
}

/** Chemin positionnel de l'élément tel qu'il apparaît dans l'export (balises vides retirées). */
function prunedPath(el: Element, pruned: Set<Element>): string {
  const parts: string[] = [];
  for (let n: Element | null = el; n; n = n.parentElement) {
    let i = 0;
    let same = 0;
    for (let s: Element | null = n.parentElement?.firstElementChild ?? null; s; s = s.nextElementSibling) {
      if (s.localName !== n.localName || pruned.has(s)) continue;
      same++;
      if (s === n) i = same;
    }
    parts.unshift(same > 1 ? `${n.localName}[${i}]` : n.localName);
  }
  return '/' + parts.join('/');
}

export { serialize };
