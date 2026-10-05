import { Component, computed, inject, input, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { KIND_BY_KEY, TabKey } from '../core/metier/catalog';
import { ObjectView, SectionView } from '../core/metier/model';
import { addObject } from '../core/edition/editor';
import { FicheComponent } from './fiche';
import { STATUS_LABELS } from './labels';

type Filtre = 'tous' | 'incomplets' | 'modifies' | 'erreurs';

@Component({
  selector: 'app-tab-view',
  imports: [FormsModule, FicheComponent],
  template: `
    @let tv = tabView();
    @let sec = section();
    @if (tv) {
      <div class="tab-layout">
        <aside class="subnav">
          @for (s of tv.sections; track s.def.key) {
            <button [class.active]="sec?.def?.key === s.def.key" (click)="openSection(s)">
              <span class="label">{{ s.def.label }}</span>
              <span class="meta">
                @if (s.def.kind) { <span class="count">{{ s.count }}</span> }
                <span class="status" [class]="'status st-' + s.status" [title]="s.note ?? ''">{{ STATUS_LABELS[s.status] }}</span>
              </span>
            </button>
          }
          @if (tab() === 'resultats') {
            <div class="engine-note">
              <strong>Moteur de calcul</strong>
              <p class="small">{{ svc.moteur.description }}</p>
              <button disabled title="Aucun moteur connecté">Recalculer</button>
            </div>
          }
        </aside>

        <section class="main">
          @if (sec) {
            @if (sec.note && (sec.status !== 'complet')) { <p class="info-box">{{ sec.note }}</p> }
            @if (tab() === 'resultats' && svc.dossier()!.meta.resultatsObsoletes) {
              <p class="warn-box">Résultats du fichier source, <strong>à recalculer</strong> : des données d'entrée ont changé ({{ svc.dossier()!.meta.motifsObsolescence.length }} modification(s) concernée(s)). Aucun graphe de dépendances de calcul fiable n'étant disponible, l'ensemble des résultats est considéré comme potentiellement obsolète.</p>
            }
            @if (sec.def.kind) {
              <div class="list-pane with-fiche">
                <div class="list-toolbar">
                  <input type="search" placeholder="Rechercher dans {{ sec.def.label.toLowerCase() }}…" [ngModel]="query()" (ngModelChange)="query.set($event)" />
                  <div class="filters">
                    @for (f of filtres; track f.key) {
                      <button [class.active]="filtre() === f.key" (click)="filtre.set(f.key)">{{ f.label }}</button>
                    }
                  </div>
                  @if (canAdd()) { <button class="primary" (click)="add()">+ Ajouter {{ kindArticle() }}</button> }
                </div>
                @if (items().length === 0) {
                  <p class="muted empty">{{ sec.count === 0 ? 'Aucun élément.' : 'Aucun élément ne correspond au filtre.' }}</p>
                } @else {
                  <ul class="cards">
                    @for (o of items(); track o.uid) {
                      <li class="obj-card" [class.selected]="selectedUid() === o.uid" (click)="select(o)">
                        <div class="obj-head">
                          <strong>{{ o.title }}</strong>
                          <span class="flags">
                            @if (o.added) { <span class="badge new">Ajouté</span> } @else if (o.modified) { <span class="badge mod">Modifié</span> }
                            @if (o.errors) { <span class="badge err">{{ o.errors }} erreur(s)</span> }
                            @if (o.missing) { <span class="badge miss">{{ o.missing }} à compléter</span> }
                          </span>
                        </div>
                        <div class="obj-summary">
                          @for (s of o.summary; track s.label) { <span><span class="muted">{{ s.label }} :</span> {{ s.value }}</span> }
                        </div>
                        @if (o.childUids.length) {
                          <div class="obj-children muted small">{{ childSummary(o) }}</div>
                        }
                      </li>
                    }
                  </ul>
                }
              </div>
              @if (selectedUid()) {
                <app-fiche class="fiche-pane" [uid]="selectedUid()!" />
              }
            } @else if (sec.singletonUid) {
              <app-fiche class="fiche-full" [uid]="sec.singletonUid" />
            }
          }
        </section>
      </div>
    }
  `,
})
export class TabViewComponent {
  readonly tab = input.required<TabKey>();
  protected readonly svc = inject(DossierService);
  protected readonly nav = inject(NavService);
  protected readonly STATUS_LABELS = STATUS_LABELS;
  protected readonly query = signal('');
  protected readonly filtre = signal<Filtre>('tous');
  protected readonly filtres: { key: Filtre; label: string }[] = [
    { key: 'tous', label: 'Tous' },
    { key: 'incomplets', label: 'Incomplets' },
    { key: 'modifies', label: 'Modifiés' },
    { key: 'erreurs', label: 'En erreur' },
  ];

  protected readonly tabView = computed(() => this.svc.model()?.tabs.find((t) => t.def.key === this.tab()) ?? null);
  protected readonly section = computed<SectionView | null>(() => {
    const tv = this.tabView();
    if (!tv) return null;
    const key = this.nav.state().section;
    return tv.sections.find((s) => s.def.key === key) ?? tv.sections[0] ?? null;
  });
  protected readonly selectedUid = computed(() => {
    const uid = this.nav.state().uid;
    const m = this.svc.model();
    const sec = this.section();
    if (!m || !sec?.def.kind) return null;
    const o = uid ? m.objects.get(uid) : undefined;
    if (o && o.section === sec.def.key) return uid;
    // sans sélection : premier objet de la liste filtrée
    return this.items()[0]?.uid ?? null;
  });

  protected readonly items = computed<ObjectView[]>(() => {
    const m = this.svc.model();
    const sec = this.section();
    if (!m || !sec?.def.kind) return [];
    const q = this.query().trim().toLowerCase();
    const f = this.filtre();
    return sec.uids
      .map((u) => m.objects.get(u)!)
      .filter((o) => !q || o.searchText.includes(q) || o.childUids.some((c) => m.objects.get(c)?.searchText.includes(q)))
      .filter((o) => {
        const tree = [o, ...o.childUids.map((c) => m.objects.get(c)!)];
        if (f === 'incomplets') return tree.some((x) => x.missing > 0);
        if (f === 'modifies') return tree.some((x) => x.modified || x.added);
        if (f === 'erreurs') return tree.some((x) => x.errors > 0);
        return true;
      });
  });

  protected readonly canAdd = computed(() => {
    const sec = this.section();
    return !!sec?.def.kind && !sec.def.results && this.svc.dossier()?.format.niveauSupport === 'complet';
  });

  protected kindArticle(): string {
    const k = KIND_BY_KEY.get(this.section()?.def.kind ?? '');
    return k ? `${k.article} ${k.label.toLowerCase()}` : '';
  }

  protected childSummary(o: ObjectView): string {
    const m = this.svc.model()!;
    const counts = new Map<string, number>();
    for (const c of o.childUids) {
      const k = m.objects.get(c)?.kind;
      if (k) counts.set(k.plural, (counts.get(k.plural) ?? 0) + 1);
    }
    return [...counts].map(([l, n]) => `${n} ${l.toLowerCase()}`).join(' · ');
  }

  protected openSection(s: SectionView): void {
    this.query.set('');
    this.nav.go(this.tab(), s.def.key, s.def.singleton ? s.singletonUid : null);
  }

  protected select(o: ObjectView): void {
    this.nav.go(this.tab(), this.section()!.def.key, o.uid);
  }

  protected add(): void {
    const sec = this.section()!;
    const kind = KIND_BY_KEY.get(sec.def.kind!)!;
    const n = sec.count + 1;
    const name = kind.nameField ? prompt(`Nom ${kind.article === 'une' ? 'de la' : 'du'} ${kind.label.toLowerCase()} :`, `${kind.label} ${n}`) : '';
    if (name === null) return;
    const uid = this.svc.run((d) => addObject(d, kind.key, { name: name || undefined }), `${kind.label} ajouté(e).`);
    if (uid) this.nav.go(this.tab(), sec.def.key, uid);
  }
}
