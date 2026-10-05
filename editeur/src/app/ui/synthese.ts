import { Component, computed, inject } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { resolvePath } from '../core/xml/safe-xml';
import { displayRounded, displayValue } from '../core/edition/value-codec';
import { schemaPath } from '../core/schema/schema-registry';
import { textOf } from '../core/edition/doc-ops';
import { GRAVITE_LABELS, STATUS_LABELS } from './labels';
import { NIVEAU_LABELS } from '../core/validation/issues';

interface Caracteristique {
  label: string;
  value: string;
  unit?: string;
}

const CARACTERISTIQUES: [string, string, string?][] = [
  ['Type de DPE', 'logement/caracteristique_generale/enum_methode_application_dpe_log_id'],
  ['Date d\'établissement', 'administratif/date_etablissement_dpe'],
  ['Année de construction', 'logement/caracteristique_generale/annee_construction'],
  ['Période de construction', 'logement/caracteristique_generale/enum_periode_construction_id'],
  ['Surface habitable du logement', 'logement/caracteristique_generale/surface_habitable_logement', 'm²'],
  ['Surface habitable de l\'immeuble', 'logement/caracteristique_generale/surface_habitable_immeuble', 'm²'],
  ['Hauteur sous plafond', 'logement/caracteristique_generale/hsp', 'm'],
  ['Zone climatique', 'logement/meteo/enum_zone_climatique_id'],
  ['Classe d\'altitude', 'logement/meteo/enum_classe_altitude_id'],
  ['Classe d\'inertie', 'logement/enveloppe/inertie/enum_classe_inertie_id'],
];

const RESULTATS: [string, string, string?][] = [
  ['Étiquette énergie', 'logement/sortie/ep_conso/classe_bilan_dpe'],
  ['Consommation (énergie primaire)', 'logement/sortie/ep_conso/ep_conso_5_usages_m2', 'kWh/m²/an'],
  ['Étiquette climat', 'logement/sortie/emission_ges/classe_emission_ges'],
  ['Émissions', 'logement/sortie/emission_ges/emission_ges_5_usages_m2', 'kg CO₂/m²/an'],
  ['Coût annuel estimé (5 usages)', 'logement/sortie/cout/cout_5_usages', '€'],
];

@Component({
  selector: 'app-synthese',
  template: `
    @let d = svc.dossier()!;
    @let m = svc.model()!;
    <div class="synthese">
      <section class="card">
        <h2>Le bien</h2>
        <dl class="kv">
          @for (c of caracteristiques(); track c.label) {
            <dt>{{ c.label }}</dt>
            <dd>@if (c.value) { {{ c.value }} {{ c.unit }} } @else { <span class="muted">non renseigné</span> }</dd>
          }
        </dl>
      </section>

      <section class="card">
        <h2>Résultats du fichier source</h2>
        @if (!d.format.resultatsPresents) {
          <p class="muted">Aucun résultat dans le fichier : ils doivent être produits par un moteur de calcul (non connecté).</p>
        } @else {
          @if (d.meta.resultatsObsoletes) {
            <p class="warn-box">Des données d'entrée ont été modifiées : ces résultats sont ceux du fichier source et sont <strong>à recalculer</strong>.</p>
          }
          <dl class="kv">
            @for (c of resultats(); track c.label) {
              <dt>{{ c.label }}</dt>
              <dd [class.stale]="d.meta.resultatsObsoletes">
                @if (c.value) {
                  @if (c.label.startsWith('Étiquette')) { <span class="etiquette" [attr.data-classe]="c.value">{{ c.value }}</span> }
                  @else { {{ c.value }} {{ c.unit }} }
                } @else { <span class="muted">absent</span> }
              </dd>
            }
          </dl>
          <p class="muted small">Valeurs lues dans le fichier, non recalculées par cet outil.</p>
        }
      </section>

      <section class="card">
        <h2>Format</h2>
        <dl class="kv">
          <dt>Famille</dt><dd>{{ d.format.libelleFamille }}</dd>
          <dt>Version du modèle</dt><dd>{{ d.format.enumVersionId ?? '—' }} {{ d.format.libelleVersion ? '— ' + d.format.libelleVersion : '' }}</dd>
          <dt>Schéma de validation</dt><dd>{{ d.format.xsd ?? 'aucun' }} @if (d.format.xsdVersion) { <span class="muted small">({{ d.format.xsdVersion }})</span> }</dd>
          <dt>Numéro ADEME</dt><dd>{{ d.format.numeroDpe ?? 'aucun' }}</dd>
          <dt>Origine</dt><dd>{{ d.meta.origine === 'import' ? 'Import de ' + d.meta.nomFichier : 'Nouveau dossier' }}</dd>
        </dl>
        @for (msg of d.format.messages; track msg) { <p class="info-box small">{{ msg }}</p> }
      </section>

      <section class="card wide">
        <h2>Contenu du dossier</h2>
        <table class="table compact">
          <thead><tr><th>Onglet</th><th>Section</th><th class="num">Éléments</th><th>État</th><th class="num">Manquants</th><th class="num">Erreurs</th></tr></thead>
          <tbody>
            @for (t of m.tabs; track t.def.key) {
              @for (s of t.sections; track s.def.key; let first = $first) {
                <tr class="clickable" (click)="nav.go(t.def.key, s.def.key, s.singletonUid)">
                  <td>@if (first) { {{ t.def.label }} }</td>
                  <td>{{ s.def.label }}</td>
                  <td class="num">{{ s.def.kind ? s.count : (s.singletonUid ? '✓' : '—') }}</td>
                  <td><span class="status" [class]="'status st-' + s.status">{{ STATUS_LABELS[s.status] }}</span></td>
                  <td class="num">{{ s.missing || '' }}</td>
                  <td class="num">{{ s.errors || '' }}</td>
                </tr>
              }
            }
          </tbody>
        </table>
      </section>

      <section class="card wide">
        <h2>Actions à effectuer</h2>
        @if (actions().length === 0) {
          <p class="muted">Aucune anomalie détectée par les contrôles en direct. Lancez la validation XSD depuis « Contrôles et export ».</p>
        } @else {
          <p class="muted small">{{ counts().erreur }} erreur(s), {{ counts().avertissement }} avertissement(s), {{ counts().info }} information(s). Cliquez pour ouvrir l'objet et le champ concernés.</p>
          <ul class="issues">
            @for (i of actions(); track i.id) {
              <li [class]="'issue g-' + i.gravite" (click)="nav.goIssue(m, i)">
                <span class="g">{{ GRAVITE_LABELS[i.gravite] }}</span>
                <span class="where">{{ where(i.uid) }}</span>
                <span class="msg">{{ i.message }}</span>
                <span class="lvl muted">{{ NIVEAU_LABELS[i.niveau] }}</span>
              </li>
            }
          </ul>
          @if (m.issues.length > actions().length) {
            <button class="link" (click)="nav.go('controles')">Voir les {{ m.issues.length }} anomalies…</button>
          }
        }
      </section>
    </div>
  `,
})
export class SyntheseComponent {
  protected readonly svc = inject(DossierService);
  protected readonly nav = inject(NavService);
  protected readonly STATUS_LABELS = STATUS_LABELS;
  protected readonly GRAVITE_LABELS = GRAVITE_LABELS;
  protected readonly NIVEAU_LABELS = NIVEAU_LABELS;

  private read(list: [string, string, string?][]): Caracteristique[] {
    this.svc.revision();
    const d = this.svc.dossier()!;
    const root = d.working.documentElement;
    return list.map(([label, rel, unit]) => {
      const raw = textOf(root, rel);
      const leaf = resolvePath(root, rel);
      const def = leaf ? d.schema?.def(schemaPath(leaf)) ?? null : null;
      const value = raw === null ? '' : def?.enumLabels || !unit ? displayValue(raw, def) : displayRounded(raw);
      return { label, value, unit };
    });
  }

  protected readonly caracteristiques = computed(() => this.read(CARACTERISTIQUES));
  protected readonly resultats = computed(() => this.read(RESULTATS));
  protected readonly actions = computed(() => (this.svc.model()?.issues ?? []).filter((i) => i.niveau !== 'format' || i.gravite !== 'info').slice(0, 25));
  protected readonly counts = computed(() => {
    const c = { erreur: 0, avertissement: 0, info: 0 };
    for (const i of this.svc.model()?.issues ?? []) c[i.gravite]++;
    return c;
  });

  protected where(uid: string | null): string {
    return (uid && this.svc.model()?.objects.get(uid)?.title) || 'Document';
  }
}
