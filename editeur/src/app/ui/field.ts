import { Component, computed, effect, ElementRef, inject, input, signal } from '@angular/core';
import { NgTemplateOutlet } from '@angular/common';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { FieldView, stateLabel } from '../core/metier/model';
import { setFieldState, setReference } from '../core/edition/editor';
import { GRAVITE_LABELS } from './labels';

@Component({
  selector: 'app-field',
  template: `
    @let f = field();
    <div class="field" [class.modified]="f.modified" [class.focused]="highlight()" [class.has-error]="hasError()" [class.na]="!f.applicable" [attr.data-rel]="f.rel">
      <label [attr.for]="id">
        {{ f.label }} @if (f.required) { <span class="req" title="Obligatoire">*</span> }
        @if (f.unit) { <span class="unit">({{ f.unit }})</span> }
        @if (f.help) { <button class="help-btn" type="button" (click)="showHelp.set(!showHelp())" [attr.aria-expanded]="showHelp()" title="Aide">?</button> }
      </label>

      <div class="control">
        @if (readonly() || f.readOnly) {
          <span class="ro">@if (f.state === 'valeur') { {{ f.display }} } @else { <span class="muted">{{ stateText() }}</span> }</span>
        } @else {
          @switch (f.input) {
            @case ('reference') {
              <select [id]="id" (change)="onRef($any($event.target).value)">
                <option value="" [selected]="!f.ref?.targets?.length">{{ f.state === 'valeur' ? '— dissocier —' : '— aucun —' }}</option>
                @if (f.state === 'valeur' && !f.ref?.targets?.length) { <option value="__keep" selected>« {{ f.raw }} » (non résolu, conservé)</option> }
                @for (c of f.ref?.candidates ?? []; track c.uid) {
                  <option [value]="c.uid" [selected]="isTarget(c.uid)">{{ c.label }}</option>
                }
              </select>
            }
            @case ('enum') { <ng-container *ngTemplateOutlet="sel" /> }
            @case ('oui_non') { <ng-container *ngTemplateOutlet="sel" /> }
            @default {
              <input [id]="id" type="text" [value]="f.state === 'valeur' ? (svc.advanced() ? f.raw : f.display) : ''"
                     [placeholder]="placeholder()" (change)="commit($any($event.target).value, $event)" (keydown.enter)="$any($event.target).blur()"
                     (keydown.escape)="$any($event.target).value = f.state === 'valeur' ? f.display : ''; $any($event.target).blur()"
                     [attr.inputmode]="f.input === 'number' || f.input === 'integer' ? 'decimal' : null" />
            }
          }
          <ng-template #sel>
            <select [id]="id" (change)="commit($any($event.target).value, $event)">
              @if (f.state !== 'valeur') { <option value="" selected>— {{ stateText() }} —</option> }
              @if (f.unknownCode) { <option [value]="f.raw" selected>Code inconnu « {{ f.raw }} » (conservé)</option> }
              @for (o of f.options; track o.code) {
                <option [value]="o.code" [selected]="o.code === f.raw">{{ svc.advanced() ? o.code + ' — ' : '' }}{{ o.label }}</option>
              }
            </select>
          </ng-template>
          <div class="state-menu">
            <button type="button" class="link small" (click)="menu.set(!menu())" title="Valeur absente, vide ou nulle">⋯</button>
            @if (menu()) {
              <div class="menu" (mouseleave)="menu.set(false)">
                <button type="button" (click)="state('absent')" [disabled]="f.state === 'absent'">Effacer (balise retirée)</button>
                <button type="button" (click)="state('vide')" [disabled]="f.state === 'vide'">Balise vide</button>
                @if (f.nillable) { <button type="button" (click)="state('nil')" [disabled]="f.state === 'nil'">Déclarer nul (xsi:nil)</button> }
              </div>
            }
          </div>
        }
      </div>

      <div class="field-meta">
        @if (f.state !== 'valeur' && f.state !== 'absent') { <span class="chip">{{ stateText() }}</span> }
        @if (f.modified) { <span class="chip mod" [title]="'Valeur à l\\'import : ' + (f.before ?? '')">modifié · avant : {{ f.before }}</span> }
        @if (f.isTableId) { <span class="chip" title="Les libellés des lignes de tables forfaitaires ne sont pas publiés dans le XSD">identifiant de ligne de table</span> }
        @if (!f.inSchema) { <span class="chip warn">hors schéma</span> }
        @if (!f.applicable && f.state === 'valeur') { <span class="chip warn" [title]="f.applicabilitySource ?? ''">non pertinent pour le choix actuel</span> }
        @if (f.ref && f.ref.status !== 'ok' && f.ref.status !== 'vide') { <span class="chip warn">{{ refStatus() }}</span> }
      </div>
      @if (showHelp() && f.help) { <p class="help">{{ f.help }} @if (f.ref) { <br /><em>{{ f.ref.doc }}</em> }</p> }
      @for (i of f.issues; track i.id) { <p [class]="'field-issue g-' + i.gravite">{{ GRAVITE_LABELS[i.gravite] }} — {{ i.message }}</p> }
      @if (error()) { <p class="field-issue g-erreur">{{ error() }}</p> }
      @if (svc.advanced()) {
        <p class="tech small"><code>{{ f.xmlPath }}</code> · {{ f.state === 'valeur' ? 'brut : « ' + f.raw + ' »' : f.state }}</p>
      }
    </div>
  `,
  imports: [NgTemplateOutlet],
})
export class FieldComponent {
  readonly field = input.required<FieldView>();
  readonly uid = input.required<string>();
  readonly kindKey = input<string | null>(null);
  readonly readonly = input(false);
  protected readonly svc = inject(DossierService);
  private readonly nav = inject(NavService);
  private readonly host = inject(ElementRef<HTMLElement>);
  protected readonly GRAVITE_LABELS = GRAVITE_LABELS;
  protected readonly showHelp = signal(false);
  protected readonly menu = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly highlight = signal(false);
  protected readonly id = 'f' + Math.random().toString(36).slice(2);

  protected readonly hasError = computed(() => this.field().issues.some((i) => i.gravite === 'erreur') || !!this.error());

  constructor() {
    effect(() => {
      this.nav.focusTick();
      const s = this.nav.state();
      if (s.uid === this.uid() && s.field === this.field().rel) {
        setTimeout(() => {
          const el = this.host.nativeElement as HTMLElement;
          el.closest('details')?.setAttribute('open', '');
          el.scrollIntoView({ block: 'center', behavior: 'smooth' });
          (el.querySelector('input,select') as HTMLElement | null)?.focus({ preventScroll: true });
          this.highlight.set(true);
          setTimeout(() => this.highlight.set(false), 2500);
        });
      }
    });
  }

  protected stateText(): string {
    const s = this.field().state;
    return s === 'valeur' ? '' : stateLabel(s);
  }

  protected placeholder(): string {
    const f = this.field();
    if (f.state !== 'valeur' && f.state !== 'absent') return stateLabel(f.state);
    if (f.input === 'date') return 'JJ/MM/AAAA';
    if (f.input === 'number') return 'ex. 12,5';
    return '';
  }

  protected refStatus(): string {
    return { non_resolu: 'référence non résolue', type_incompatible: 'cible d\'un type non prévu', ambigu: 'référence ambiguë' }[this.field().ref!.status] ?? '';
  }

  protected isTarget(uid: string): boolean {
    return this.field().ref?.targets.some((t) => t.uid === uid) ?? false;
  }

  protected commit(value: string, event: Event): void {
    const f = this.field();
    this.error.set(null);
    if (value === '' && f.state !== 'valeur') return;
    if (value === '') {
      // champ vidé par l'utilisateur : on ne supprime pas en silence, on propose l'action explicite
      this.error.set('Pour retirer la valeur, utilisez le menu ⋯ (effacer, vider ou déclarer nul).');
      (event.target as HTMLInputElement).value = f.display;
      return;
    }
    if (value === f.raw || (value === f.display && f.state === 'valeur')) return;
    const ok = this.svc.setValue(this.uid(), f.rel, value);
    if (!ok) {
      this.error.set('Saisie refusée : voir le message.');
      (event.target as HTMLInputElement).value = f.state === 'valeur' ? f.display : '';
    }
  }

  protected onRef(value: string): void {
    if (value === '__keep') return;
    const f = this.field();
    if (value === '' && f.state === 'valeur' && !confirm(`Dissocier « ${f.label} » ? La référence sera retirée.`)) return;
    this.svc.run((d) => setReference(d, this.uid(), f.ref!.key, value || null));
  }

  protected state(s: 'absent' | 'vide' | 'nil'): void {
    this.menu.set(false);
    this.svc.run((d) => setFieldState(d, this.uid(), this.field().rel, s));
  }
}
