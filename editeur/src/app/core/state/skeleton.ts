import { SchemaHandle } from '../schema/schema-registry';
import { parseXml } from '../xml/safe-xml';
import { UidCounter, annotate } from '../xml/uid';
import { createSkeleton, ensurePath, setLeafText } from '../edition/doc-ops';

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
