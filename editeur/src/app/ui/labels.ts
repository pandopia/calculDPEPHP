import { SectionStatus } from '../core/metier/model';
import { Gravite } from '../core/validation/issues';

export const STATUS_LABELS: Record<SectionStatus, string> = {
  complet: 'Complet',
  a_completer: 'À compléter',
  erreurs: 'Erreurs',
  vide: 'Aucun élément (facultatif)',
  non_applicable: 'Non applicable',
  absent: 'Absent du fichier',
};

export const GRAVITE_LABELS: Record<Gravite, string> = {
  erreur: 'Erreur',
  avertissement: 'Avertissement',
  info: 'Information',
};

export function frDate(iso: string | null): string {
  if (!iso) return '';
  return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}
