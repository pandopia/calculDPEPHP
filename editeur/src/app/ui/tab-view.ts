import { Component, computed, inject, input, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { KIND_BY_KEY, TabKey } from '../core/metier/catalog';
import { ObjectView, SectionView } from '../core/metier/model';
import { addObject } from '../core/edition/editor';
import { FicheComponent, GroupComponent } from './fiche';
import { BilanComponent } from './bilan';

type Filtre = 'tous' | 'incomplets' | 'modifies' | 'erreurs';

/** Une section : liste compacte à gauche, fiche de l'objet sélectionné à droite. */
@Component({
  selector: 'app-tab-view',
  imports: [FormsModule, FicheComponent, GroupComponent, BilanComponent],
  template: `
    @let sec = section();
    @if (sec) {
      @if (sec.def.key === 'bilan') {
        <app-bilan />
      } @else if (sec.def.kind) {
        <div class="split">
          <div class="list-pane">
            <div class="list-head">
              <h2>{{ sec.def.label }} <span class="muted">{{ sec.count }}</span></h2>
              @if (canAdd()) { <button class="primary small" (click)="add()">+ {{ kindLabel() }}</button> }
            </div>
            @if (sec.count > 6 || filtre() !== 'tous' || query()) {
              <div class="list-toolbar">
                <input type="search" placeholder="Filtrer…" [ngModel]="query()" (ngModelChange)="query.set($event)" />
                @for (f of filtres(); track f.key) {
                  <button class="chip-btn" [class.active]="filtre() === f.key" (click)="filtre.set(filtre() === f.key ? 'tous' : f.key)">{{ f.label }} {{ f.n }}</button>
                }
              </div>
            }
            @if (sec.note && sec.count === 0) { <p class="muted small">{{ sec.note }}</p> }
            @if (items().length) {
              <ul class="obj-list">
                @for (o of items(); track o.uid) {
                  <li [class.selected]="selectedUid() === o.uid" (click)="select(o)">
                    <div class="ol-head">
                      <span class="ol-title" [title]="o.title">{{ o.title }}</span>
                      <span class="row-flags">
                        @if (o.errors) { <span class="dot err" title="Erreur">!</span> } @else if (o.missing) { <span class="dot miss" title="À compléter">{{ o.missing }}</span> }
                        @if (o.added) { <span class="mod-dot new" title="Ajouté"></span> } @else if (o.modified) { <span class="mod-dot" title="Modifié"></span> }
                      </span>
                    </div>
                    <div class="ol-sub">
                      @for (v of rowValues(o); track $index) { <span>{{ v }}</span> }
                      @if (o.childUids.length) { <span>{{ childSummary(o) }}</span> }
                    </div>
                  </li>
                }
              </ul>
            } @else if (sec.count) { <p class="muted">Aucun élément ne correspond.</p> }
          </div>
          @if (selectedUid()) { <app-fiche class="fiche-pane" [uid]="selectedUid()!" /> }
        </div>
      } @else if (sec.singletonUid) {
        <div class="single"><app-fiche [uid]="sec.singletonUid" /></div>
      } @else if (absent(); as af) {
        <div class="single">
          <div class="fiche">
            <header class="fiche-head"><div class="fiche-title"><h2>{{ af.title }}</h2>
              <div class="sub"><span class="kind">Bloc absent du fichier : il sera créé dès qu'une valeur y sera saisie, et contrôlé à ce moment-là.</span></div>
            </div></header>
            @for (g of af.groups; track g.key) { <app-group [group]="g" [uid]="af.uid" [top]="true" /> }
          </div>
        </div>
      } @else {
        <div class="single"><div class="fiche"><h2>{{ sec.def.label }}</h2><p class="muted">{{ sec.note }}</p></div></div>
      }
    }
  `,
})
export class TabViewComponent {
  readonly tab = input.required<TabKey>();
  protected readonly svc = inject(DossierService);
  protected readonly nav = inject(NavService);
  protected readonly query = signal('');
  protected readonly filtre = signal<Filtre>('tous');

  protected readonly tabView = computed(() => this.svc.model()?.tabs.find((t) => t.def.key === this.tab()) ?? null);
  protected readonly section = computed<SectionView | null>(() => {
    const tv = this.tabView();
    if (!tv) return null;
    const key = this.nav.state().section;
    return tv.sections.find((s) => s.def.key === key) ?? tv.sections[0] ?? null;
  });

  private readonly all = computed<ObjectView[]>(() => {
    const m = this.svc.model();
    const sec = this.section();
    return m && sec?.def.kind ? sec.uids.map((u) => m.objects.get(u)!) : [];
  });
  private tree(o: ObjectView): ObjectView[] {
    const m = this.svc.model()!;
    return [o, ...o.childUids.map((c) => m.objects.get(c)!)];
  }
  protected readonly filtres = computed(() => {
    const all = this.all();
    const n = (f: (o: ObjectView) => boolean) => all.filter((o) => this.tree(o).some(f)).length;
    return ([
      { key: 'incomplets', label: 'À compléter', n: n((x) => x.missing > 0) },
      { key: 'erreurs', label: 'En erreur', n: n((x) => x.errors > 0) },
      { key: 'modifies', label: 'Modifiés', n: n((x) => x.modified || x.added) },
    ] as { key: Filtre; label: string; n: number }[]).filter((f) => f.n > 0 || this.filtre() === f.key);
  });
  protected readonly items = computed<ObjectView[]>(() => {
    const q = this.query().trim().toLowerCase();
    const f = this.filtre();
    return this.all()
      .filter((o) => !q || this.tree(o).some((x) => x.searchText.includes(q)))
      .filter((o) => {
        const t = this.tree(o);
        if (f === 'incomplets') return t.some((x) => x.missing > 0);
        if (f === 'modifies') return t.some((x) => x.modified || x.added);
        if (f === 'erreurs') return t.some((x) => x.errors > 0);
        return true;
      });
  });

  protected readonly selectedUid = computed(() => {
    const uid = this.nav.state().uid;
    const m = this.svc.model();
    const sec = this.section();
    if (!m || !sec?.def.kind) return null;
    const o = uid ? m.objects.get(uid) : undefined;
    if (o && o.section === sec.def.key) return uid;
    return this.items()[0]?.uid ?? null;
  });

  /** bloc unique absent du fichier : champs vides saisissables */
  protected readonly absent = computed(() => {
    this.svc.revision();
    const sec = this.section();
    if (!sec?.def.singleton || sec.singletonUid || sec.def.results) return null;
    if (this.svc.dossier()?.format.niveauSupport !== 'complet') return null;
    return this.svc.absentFiche(sec.def.singleton, sec.def.label);
  });

  protected readonly canAdd = computed(() => {
    const sec = this.section();
    return !!sec?.def.kind && !sec.def.results && this.svc.dossier()?.format.niveauSupport === 'complet';
  });

  protected kindLabel(): string {
    return KIND_BY_KEY.get(this.section()?.def.kind ?? '')?.label ?? '';
  }

  /** valeurs clés en une ligne, sans étiquettes (l'ordre du type d'objet les rend lisibles) */
  protected rowValues(o: ObjectView): string[] {
    return o.summary.slice(0, 4).map((s) =>
      /^\d+$/.test(s.value) || /^(oui|non|inconnu|inconnue)$/i.test(s.value) ? `${s.label} : ${s.value.toLowerCase()}` : s.value,
    );
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

  protected select(o: ObjectView): void {
    this.nav.go(this.tab(), this.section()!.def.key, o.uid);
  }

  protected add(): void {
    const sec = this.section()!;
    const kind = KIND_BY_KEY.get(sec.def.kind!)!;
    const name = kind.nameField ? prompt(`Nom ${kind.article === 'une' ? 'de la' : 'du'} ${kind.label.toLowerCase()} :`, `${kind.label} ${sec.count + 1}`) : '';
    if (name === null) return;
    const uid = this.svc.run((d) => addObject(d, kind.key, { name: name || undefined }), `${kind.label} ajouté(e).`);
    if (uid) this.nav.go(this.tab(), sec.def.key, uid);
  }
}
