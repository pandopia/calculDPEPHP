import { Component, computed, inject, signal } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { Gravite, Issue, Niveau, NIVEAU_LABELS } from '../core/validation/issues';
import { Change } from '../core/edition/change-set';
import { removeAllUnknown, unknownElements } from '../core/edition/editor';
import { GRAVITE_LABELS } from './labels';
import { FicheComponent } from './fiche';

const NIVEAUX: Niveau[] = ['xml', 'format', 'xsd', 'champ', 'completude', 'references', 'metier', 'plausibilite', 'resultats'];

interface IssueGroup {
  key: string;
  label: string;
  issues: Issue[];
  errors: number;
  warnings: number;
}

/** Vérification et export : l'essentiel d'abord, le détail sur demande. */
@Component({
  selector: 'app-controles',
  imports: [FicheComponent],
  template: `
    @let d = svc.dossier()!;
    @let m = svc.model()!;
    <div class="controles">
      <section class="card export-card">
        <div class="export-status">
          <div class="kpi"><span class="kpi-v" [class.err-text]="counts().erreur">{{ counts().erreur }}</span><span class="kpi-l">erreur(s)</span></div>
          <div class="kpi"><span class="kpi-v" [class.warn-text]="counts().avertissement">{{ counts().avertissement }}</span><span class="kpi-l">avertissement(s)</span></div>
          <div class="kpi"><span class="kpi-v">{{ svc.changes().length }}</span><span class="kpi-l">modification(s)</span></div>
          <div class="kpi xsd">
            <span class="kpi-l">Schéma {{ d.format.xsd ?? '—' }}</span>
            @if (xsd(); as r) {
              <span [class.ok-text]="r.valid" [class.err-text]="r.valid === false">{{ r.valid ? '✓ conforme' : r.valid === false ? '✕ ' + r.issues.length + ' écart(s)' : 'non vérifiable' }}</span>
              @if (r.revision !== d.revision) { <span class="muted small">avant les dernières modifications</span> }
            } @else { <span class="muted">non vérifié</span> }
          </div>
        </div>
        <div class="export-actions">
          <button (click)="svc.validateXsd()" [disabled]="svc.xsdRunning() || !d.schema">{{ svc.xsdRunning() ? 'Validation…' : 'Valider contre le XSD' }}</button>
          <button (click)="svc.rapportPdf()" [disabled]="svc.pdfEnCours()">{{ svc.pdfEnCours() ? 'PDF en cours…' : 'Rapport PDF' }}</button>
          <button class="primary" (click)="exportOpen.set(true)">Exporter le XML…</button>
        </div>
        <p class="muted small">Validation locale par libxml2. Un fichier conforme au XSD n'est ni recalculé, ni validé ni accepté par l'ADEME pour autant. Le brouillon s'enregistre tout seul dans ce navigateur.</p>
      </section>

      <section class="card">
        <div class="section-head">
          <h2>Points à traiter</h2>
          <div class="filters">
            @for (g of gravites; track g) {
              <button class="chip-btn" [class.active]="gravite() === g" (click)="gravite.set(g)">{{ GRAVITE_LABELS[g] }}s {{ counts()[g] }}</button>
            }
          </div>
        </div>
        @if (inconnues() > 0) {
          <p class="unknown-bar small">{{ inconnues() }} balise(s) hors schéma : conservées à l'export, mais refusées par la validation XSD.
            <button class="small" (click)="retirerInconnues()">Retirer les balises hors schéma</button></p>
        }
        @if (groups().length === 0) { <p class="muted">Rien à signaler pour ce filtre.</p> }
        <ul class="igroups">
          @for (g of groups(); track g.key) {
            <li class="igroup">
              <button class="igroup-head" (click)="toggle(g.key)">
                <span class="caret">{{ open().has(g.key) ? '▾' : '▸' }}</span>
                <span class="igroup-label">{{ g.label }}</span>
                <span class="igroup-preview muted small">{{ open().has(g.key) ? '' : g.issues[0].message }}</span>
                @if (g.errors) { <span class="dot err">{{ g.errors }}</span> }
                @if (g.warnings) { <span class="dot miss">{{ g.warnings }}</span> }
                @if (!g.errors && !g.warnings) { <span class="count muted">{{ g.issues.length }}</span> }
              </button>
              @if (open().has(g.key)) {
                <ul class="iitems">
                  @for (i of g.issues; track i.id) {
                    <li [class]="'g-' + i.gravite" (click)="nav.goIssue(m, i)">
                      <span>{{ i.message }}</span>
                      <span class="muted small">{{ NIVEAU_LABELS[i.niveau] }}</span>
                      @if (svc.advanced() && (i.detail || i.xmlPath)) { <code class="small">{{ i.detail ?? i.xmlPath }}</code> }
                      @if (i.source && svc.advanced()) { <span class="muted small">Source : {{ i.source }}</span> }
                    </li>
                  }
                </ul>
              }
            </li>
          }
        </ul>
        <details class="levels-details">
          <summary>Détail par niveau de contrôle</summary>
          <table class="table compact">
            <thead><tr><th>Niveau</th><th class="num">Erreurs</th><th class="num">Avertissements</th><th class="num">Informations</th></tr></thead>
            <tbody>
              @for (n of niveaux; track n) {
                <tr><td>{{ NIVEAU_LABELS[n] }}</td><td class="num">{{ count(n, 'erreur') || '' }}</td><td class="num">{{ count(n, 'avertissement') || '' }}</td><td class="num">{{ count(n, 'info') || '' }}</td></tr>
              }
            </tbody>
          </table>
        </details>
      </section>

      <section class="card">
        <h2>Modifications depuis {{ d.meta.origine === 'import' ? 'l\\'import' : 'la création' }} <span class="muted">{{ svc.changes().length }}</span></h2>
        @if (svc.changes().length === 0) {
          <p class="muted">Aucune modification.</p>
        } @else {
          <table class="table compact">
            <thead><tr><th>Objet</th><th>Champ</th><th>Avant</th><th>Après</th></tr></thead>
            <tbody>
              @for (c of svc.changes(); track $index) {
                <tr class="clickable" (click)="openChange(c)">
                  <td>@if (c.type === 'ajout') { <span class="badge new">ajout</span> } @else if (c.type === 'suppression') { <span class="badge err">suppression</span> } {{ c.objet }}</td>
                  <td>{{ c.champ ?? '' }}</td>
                  <td class="muted">{{ c.avant ?? '' }}</td>
                  <td><strong>{{ c.apres ?? '' }}</strong></td>
                </tr>
              }
            </tbody>
          </table>
        }
      </section>

      @if (m.otherSections.length) {
        <section class="card">
          <h2>Autres données du fichier</h2>
          <p class="small muted">Sections non couvertes par la navigation (logement neuf, tertiaire, extensions…), conservées à l'export, ouvertes ici en vue générique.</p>
          <div class="links">
            @for (s of m.otherSections; track s.uid) { <a href="" class="obj-chip" (click)="$event.preventDefault(); nav.go('controles', 'autres', s.uid)">{{ s.label }}</a> }
          </div>
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
            <li>{{ counts().erreur }} erreur(s), {{ counts().avertissement }} avertissement(s).</li>
            <li>Validation XSD : @if (xsd(); as r) { {{ r.message }} } @else { non lancée. }</li>
            @if (d.meta.resultatsObsoletes) { <li><strong>Les résultats ne sont pas à jour des dernières modifications</strong> ({{ svc.origineResultats() }}) : lancez le calcul avant d'exporter.</li> }
            <li>Version du modèle : {{ d.format.enumVersionId ?? 'inconnue' }} (pas de migration).</li>
          </ul>
          <p class="warn-box small">Document de travail : ni recalculé, ni validé par l'ADEME, sans numéro ADEME autre que celui du fichier d'origine.</p>
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
  protected readonly gravite = signal<Gravite>('avertissement');
  protected readonly open = signal(new Set<string>());
  protected readonly exportOpen = signal(false);

  protected readonly xsd = computed(() => this.svc.xsdReport());
  protected readonly otherOpen = computed(() => {
    const uid = this.nav.state().uid;
    return uid && this.svc.model()?.otherSections.some((s) => s.uid === uid) ? uid : null;
  });
  protected readonly all = computed<Issue[]>(() => [...(this.svc.xsdReport()?.issues ?? []), ...(this.svc.model()?.issues ?? [])]);
  protected readonly counts = computed(() => {
    const c = { erreur: 0, avertissement: 0, info: 0 };
    for (const i of this.all()) c[i.gravite]++;
    return c;
  });

  /** anomalies regroupées par objet, pour la gravité choisie (erreurs + avertissements par défaut) */
  protected readonly groups = computed<IssueGroup[]>(() => {
    const g = this.gravite();
    const wanted = (i: Issue) => (g === 'info' ? i.gravite === 'info' : g === 'erreur' ? i.gravite === 'erreur' : i.gravite !== 'info');
    const map = new Map<string, IssueGroup>();
    for (const i of this.all().filter(wanted)) {
      const key = i.uid ?? i.nav?.section ?? 'document';
      if (!map.has(key)) map.set(key, { key, label: this.where(i), issues: [], errors: 0, warnings: 0 });
      const grp = map.get(key)!;
      grp.issues.push(i);
      if (i.gravite === 'erreur') grp.errors++;
      if (i.gravite === 'avertissement') grp.warnings++;
    }
    return [...map.values()].sort((a, b) => b.errors - a.errors || b.warnings - a.warnings);
  });

  protected readonly inconnues = computed(() => {
    this.svc.revision();
    const d = this.svc.dossier();
    return d ? unknownElements(d).length : 0;
  });

  protected retirerInconnues(): void {
    const n = this.inconnues();
    if (!confirm(`Retirer ${n} balise(s) hors schéma ? Elles ne seront plus dans le XML exporté (annulable par Ctrl+Z).`)) return;
    this.svc.run((d) => removeAllUnknown(d), `${n} balise(s) hors schéma retirée(s).`);
  }

  protected toggle(key: string): void {
    const next = new Set(this.open());
    if (next.has(key)) next.delete(key);
    else next.add(key);
    this.open.set(next);
  }

  protected count(n: Niveau, g: Gravite): number {
    return this.all().filter((i) => i.niveau === n && i.gravite === g).length;
  }

  protected where(i: Issue): string {
    const o = i.uid ? this.svc.model()?.objects.get(i.uid) : undefined;
    if (o) return o.kind ? `${o.kind.label} · ${o.title}` : o.title;
    return i.nav ? i.nav.section : 'Document';
  }

  protected openChange(c: Change): void {
    const m = this.svc.model();
    if (m && c.uid) this.nav.goObject(m, c.uid, c.field);
  }

  protected doExport(): void {
    this.svc.download(this.svc.exportXml(), `${this.svc.baseName()}-travail.xml`, 'application/xml');
    this.exportOpen.set(false);
  }
}
