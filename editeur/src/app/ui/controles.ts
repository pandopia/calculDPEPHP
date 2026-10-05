import { Component, computed, inject, signal } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { Gravite, Issue, Niveau, NIVEAU_LABELS } from '../core/validation/issues';
import { Change } from '../core/edition/change-set';
import { GRAVITE_LABELS } from './labels';
import { FicheComponent } from './fiche';

const NIVEAUX: Niveau[] = ['xml', 'format', 'xsd', 'champ', 'completude', 'references', 'metier', 'plausibilite', 'resultats'];

@Component({
  selector: 'app-controles',
  imports: [FicheComponent],
  template: `
    @let d = svc.dossier()!;
    @let m = svc.model()!;
    <div class="controles">
      <section class="card">
        <h2>Validation</h2>
        <table class="table compact levels">
          <thead><tr><th>Niveau de contrôle</th><th class="num">Erreurs</th><th class="num">Avertissements</th><th class="num">Informations</th><th></th></tr></thead>
          <tbody>
            @for (n of niveaux; track n) {
              <tr [class.active]="niveau() === n" class="clickable" (click)="niveau.set(niveau() === n ? null : n)">
                <td>{{ NIVEAU_LABELS[n] }}
                  @if (n === 'xml') { <span class="muted small">— vérifié à l'import</span> }
                  @if (n === 'xsd') {
                    <span class="muted small">— @if (xsd(); as r) { {{ r.message }} @if (r.revision !== svc.revision() && r.revision !== d.revision) { (avant les dernières modifications) } } @else { non lancée }</span>
                  }
                  @if (n === 'plausibilite') { <span class="muted small">— indicatif, non réglementaire</span> }
                </td>
                <td class="num">{{ count(n, 'erreur') || '' }}</td>
                <td class="num">{{ count(n, 'avertissement') || '' }}</td>
                <td class="num">{{ count(n, 'info') || '' }}</td>
                <td class="right">@if (n === 'xsd') { <button class="primary small" (click)="$event.stopPropagation(); svc.validateXsd()" [disabled]="svc.xsdRunning() || !d.schema">{{ svc.xsdRunning() ? 'Validation…' : 'Lancer la validation XSD' }}</button> }</td>
              </tr>
            }
          </tbody>
        </table>
        <p class="muted small">La validation XSD est faite localement par libxml2 contre {{ d.format.xsd ?? 'aucun schéma' }}{{ d.format.enTeteObservatoire ? ', en-tête de l\\'observatoire (numero_dpe, statut) exclu' : '' }}. Un fichier conforme au XSD n'est <strong>ni recalculé, ni validé ni accepté par l'ADEME</strong> pour autant.</p>

        <div class="filters">
          <button [class.active]="gravite() === null" (click)="gravite.set(null)">Toutes</button>
          @for (g of gravites; track g) { <button [class.active]="gravite() === g" (click)="gravite.set(g)">{{ GRAVITE_LABELS[g] }}s</button> }
          @if (niveau()) { <span class="chip">{{ NIVEAU_LABELS[niveau()!] }} <button class="link small" (click)="niveau.set(null)">✕</button></span> }
        </div>
        <ul class="issues">
          @for (i of filtered(); track i.id) {
            <li [class]="'issue g-' + i.gravite" (click)="nav.goIssue(m, i)">
              <span class="g">{{ GRAVITE_LABELS[i.gravite] }}</span>
              <span class="where">{{ where(i) }}</span>
              <span class="msg">{{ i.message }} @if (svc.advanced() && i.detail) { <br /><code class="small">{{ i.detail }}</code> } @if (svc.advanced() && i.xmlPath) { <br /><code class="small">{{ i.xmlPath }}</code> } @if (i.source) { <br /><span class="muted small">Source : {{ i.source }}</span> }</span>
              <span class="lvl muted">{{ NIVEAU_LABELS[i.niveau] }}</span>
            </li>
          } @empty { <li class="muted">Aucune anomalie pour ce filtre.</li> }
        </ul>
        @if (filtered().length >= limit()) { <button class="link" (click)="limit.set(limit() + 200)">Afficher plus…</button> }
      </section>

      <section class="card">
        <h2>Modifications depuis {{ d.meta.origine === 'import' ? 'l\\'import' : 'la création' }} ({{ svc.changes().length }})</h2>
        @if (svc.changes().length === 0) {
          <p class="muted">Aucune modification : l'export sera identique au fichier d'origine (hors mise en forme).</p>
        } @else {
          <table class="table compact">
            <thead><tr><th>Objet</th><th>Champ</th><th>Avant</th><th>Après</th></tr></thead>
            <tbody>
              @for (c of svc.changes(); track $index) {
                <tr class="clickable" (click)="openChange(c)">
                  <td>@if (c.type === 'ajout') { <span class="badge new">Ajout</span> } @else if (c.type === 'suppression') { <span class="badge err">Suppression</span> } {{ c.objet }}</td>
                  <td>{{ c.champ ?? '' }} @if (svc.advanced()) { <br /><code class="small">{{ c.xmlPath }}</code> }</td>
                  <td>{{ c.avant ?? '' }}</td>
                  <td><strong>{{ c.apres ?? '' }}</strong></td>
                </tr>
              }
            </tbody>
          </table>
        }
        @if (d.meta.resultatsObsoletes) {
          <details>
            <summary>Résultats à recalculer — {{ d.meta.motifsObsolescence.length }} modification(s) concernée(s)</summary>
            <ul class="small">@for (x of d.meta.motifsObsolescence; track x) { <li>{{ x }}</li> }</ul>
          </details>
        }
      </section>

      <section class="card">
        <h2>Enregistrer et exporter</h2>
        <div class="export-grid">
          <div>
            <h3>Projet de travail</h3>
            <p class="small">Enregistré automatiquement dans ce navigateur à chaque modification (brouillon, même incomplet ou en erreur). Pour le reprendre sur un autre poste, téléchargez-le.</p>
            <button (click)="svc.save()">Enregistrer maintenant</button>
            <button (click)="downloadDraft()">Télécharger le brouillon (.json)</button>
          </div>
          <div>
            <h3>XML de travail</h3>
            <p class="small">XML DPE produit à partir du dossier : document importé conservé, modifications appliquées. Identifiants internes retirés.</p>
            <button class="primary" (click)="exportOpen.set(true)">Exporter le XML de travail…</button>
          </div>
          <div>
            <h3>Fichier d'origine</h3>
            @if (d.original) {
              <p class="small">Le fichier importé est conservé tel quel, octet pour octet.</p>
              <button (click)="svc.download(d.original, d.meta.nomFichier ?? 'original.xml', 'application/xml')">Télécharger l'original</button>
            } @else { <p class="muted small">Dossier créé dans l'éditeur : pas de fichier d'origine.</p> }
          </div>
        </div>
      </section>

      @if (m.otherSections.length) {
        <section class="card">
          <h2>Autres données du fichier</h2>
          <p class="small muted">Sections présentes dans le fichier mais non couvertes par les onglets métier (branche logement neuf ou tertiaire, extensions de logiciel…). Elles sont conservées à l'identique à l'export et s'ouvrent ici en vue générique, construite à partir du schéma.</p>
          <ul>
            @for (s of m.otherSections; track s.uid) {
              <li><a href="" (click)="$event.preventDefault(); nav.go('controles', 'autres', s.uid)">{{ s.label }}</a> <code class="small muted">{{ s.path }}</code></li>
            }
          </ul>
          @if (otherOpen(); as uid) { <app-fiche [uid]="uid" (closed)="nav.go('controles')" /> }
        </section>
      }
    </div>

    @if (exportOpen()) {
      <div class="modal-backdrop" (click)="exportOpen.set(false)">
        <div class="modal" (click)="$event.stopPropagation()" role="dialog" aria-modal="true">
          <h2>Exporter le XML de travail</h2>
          <ul>
            <li>{{ svc.changes().length }} modification(s) depuis {{ d.meta.origine === 'import' ? 'l\\'import' : 'la création' }}.</li>
            <li>{{ counts().erreur }} erreur(s) et {{ counts().avertissement }} avertissement(s) détectés par les contrôles.</li>
            <li>Validation XSD : @if (xsd(); as r) { {{ r.message }} } @else { non lancée. }</li>
            @if (d.meta.resultatsObsoletes) { <li><strong>Les résultats (consommations, étiquettes…) sont ceux du fichier source et n'ont pas été recalculés.</strong></li> }
            <li>Version du modèle : {{ d.format.enumVersionId ?? 'inconnue' }} — le fichier n'est pas migré vers une autre version.</li>
          </ul>
          <p class="warn-box small">Ce XML est un document de travail. Il n'est ni recalculé, ni validé par l'ADEME, et ne porte aucun numéro ADEME qui n'ait figuré dans le fichier d'origine.</p>
          <div class="modal-actions">
            <button (click)="exportOpen.set(false)">Annuler</button>
            <button class="primary" (click)="doExport()">Télécharger le XML</button>
          </div>
        </div>
      </div>
    }
  `,
})
export class ControlesComponent {
  protected readonly svc = inject(DossierService);
  protected readonly nav = inject(NavService);
  protected readonly NIVEAU_LABELS = NIVEAU_LABELS;
  protected readonly GRAVITE_LABELS = GRAVITE_LABELS;
  protected readonly niveaux = NIVEAUX;
  protected readonly gravites: Gravite[] = ['erreur', 'avertissement', 'info'];
  protected readonly niveau = signal<Niveau | null>(null);
  protected readonly gravite = signal<Gravite | null>(null);
  protected readonly limit = signal(200);
  protected readonly exportOpen = signal(false);

  protected readonly xsd = computed(() => this.svc.xsdReport());
  protected readonly otherOpen = computed(() => {
    const uid = this.nav.state().uid;
    return uid && this.svc.model()?.otherSections.some((s) => s.uid === uid) ? uid : null;
  });
  protected readonly all = computed<Issue[]>(() => [...(this.svc.xsdReport()?.issues ?? []), ...(this.svc.model()?.issues ?? [])]);
  protected readonly filtered = computed(() =>
    this.all()
      .filter((i) => !this.niveau() || i.niveau === this.niveau())
      .filter((i) => !this.gravite() || i.gravite === this.gravite())
      .slice(0, this.limit()),
  );
  protected readonly counts = computed(() => {
    const c = { erreur: 0, avertissement: 0, info: 0 };
    for (const i of this.all()) c[i.gravite]++;
    return c;
  });

  protected count(n: Niveau, g: Gravite): number {
    return this.all().filter((i) => i.niveau === n && i.gravite === g).length;
  }

  protected where(i: Issue): string {
    return (i.uid && this.svc.model()?.objects.get(i.uid)?.title) || (i.nav ? i.nav.section : 'Document');
  }

  protected openChange(c: Change): void {
    const m = this.svc.model();
    if (m && c.uid) this.nav.goObject(m, c.uid, c.field);
  }

  protected doExport(): void {
    this.svc.download(this.svc.exportXml(), `${this.svc.baseName()}-travail.xml`, 'application/xml');
    this.exportOpen.set(false);
  }

  protected downloadDraft(): void {
    this.svc.download(JSON.stringify(this.svc.dossier()!.toDraft()), `${this.svc.baseName()}.brouillon.json`, 'application/json');
  }
}
