import { SchemaHandle } from '../schema/schema-registry';
import { parseXml } from '../xml/safe-xml';
import { UidCounter, annotate } from '../xml/uid';
import { createSkeleton, ensurePath, setLeafText } from '../edition/doc-ops';
import { addObject, setFieldValue } from '../edition/editor';
import { Dossier } from './dossier';

/**
 * Document vierge d'un DPE logement existant, produit à partir du XSD.
 *
 * Seules sont écrites les valeurs choisies par l'utilisateur à la création :
 * version du modèle, modèle de DPE (3CL logement — c'est le périmètre choisi)
 * et type de bien. La structure obligatoire (conteneurs) est créée ; aucune
 * autre valeur n'est inventée. L'attribut racine obligatoire `version` reste à
 * renseigner. Les résultats (`sortie`) ne sont pas créés : ils relèvent d'un
 * moteur de calcul.
 */
export interface NewDossierOptions {
  version: string;
  methodeApplication: string;
}

export function buildSkeleton(schema: SchemaHandle, options: NewDossierOptions): XMLDocument {
  const doc = parseXml('<dpe/>');
  const counter = new UidCounter();
  annotate(doc.documentElement, counter, () => true);
  const root = doc.documentElement;
  createSkeleton(root, schema, counter);
  setLeafText(ensurePath(root, 'administratif/enum_version_id', schema, counter), options.version);
  setLeafText(ensurePath(root, 'administratif/enum_modele_dpe_id', schema, counter), '1');
  const logement = ensurePath(root, 'logement', schema, counter);
  createSkeleton(logement, schema, counter);
  setLeafText(ensurePath(logement, 'caracteristique_generale/enum_methode_application_dpe_log_id', schema, counter), options.methodeApplication);
  return doc;
}

/**
 * Objets présents dans tout logement, créés d'office dans un dossier vierge :
 * un plancher bas, un plancher haut, quatre murs (un par orientation),
 * une ventilation (le XSD en exige au moins une), une installation de
 * chauffage (avec un générateur et un émetteur, exigés par le XSD) et une
 * installation d'ECS (avec un générateur). Seuls le nom et, pour les murs,
 * l'orientation sont renseignés ; tout le reste est à saisir.
 */
export function addDefaultObjects(d: Dossier): void {
  addObject(d, 'plancher_bas', { name: 'Plancher bas' });
  addObject(d, 'plancher_haut', { name: 'Plancher haut' });
  for (const [code, label] of [['2', 'nord'], ['1', 'sud'], ['3', 'est'], ['4', 'ouest']]) {
    const mur = addObject(d, 'mur', { name: `Mur ${label}` });
    setFieldValue(d, mur, 'donnee_entree/enum_orientation_id', code);
  }
  addObject(d, 'ventilation', { name: 'Ventilation' });
  const ch = addObject(d, 'installation_chauffage', { name: 'Installation de chauffage' });
  addObject(d, 'generateur_chauffage', { parentUid: ch, name: 'Générateur de chauffage' });
  addObject(d, 'emetteur_chauffage', { parentUid: ch, name: 'Émetteur de chauffage' });
  const ecs = addObject(d, 'installation_ecs', { name: 'Installation d\'eau chaude sanitaire' });
  addObject(d, 'generateur_ecs', { parentUid: ecs, name: 'Générateur d\'ECS' });
}
