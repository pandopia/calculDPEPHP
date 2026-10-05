import { Component, computed, inject, input, output, signal } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { DeletionPlan, executeDeletion, planDeletion, Resolution } from '../core/edition/editor';

@Component({
  selector: 'app-delete-dialog',
  template: `
    <div class="modal-backdrop" (click)="done.emit(false)">
      <div class="modal" (click)="$event.stopPropagation()" role="dialog" aria-modal="true">
        @let p = plan();
        @if (!p) {
          <p>{{ error() }}</p>
          <button (click)="done.emit(false)">Fermer</button>
        } @else {
          <h2>Supprimer {{ p.deleted[0].label }}</h2>
          <h3>Éléments supprimés ({{ p.deleted.length }})</h3>
          <ul class="plan">
            @for (x of p.deleted; track x.uid) { <li><strong>{{ x.label }}</strong> <span class="muted small">— {{ x.reason }}</span></li> }
          </ul>
          @if (p.dependencies.length) {
            <h3>Objets qui y font référence ({{ p.dependencies.length }})</h3>
            <p class="muted small">Aucune référence ne restera cassée : choisissez pour chacun comment le lien est résolu.</p>
            <table class="table compact">
              <thead><tr><th>Objet</th><th>Lien</th><th>Résolution</th></tr></thead>
              <tbody>
                @for (dep of p.dependencies; track dep.key) {
                  <tr>
                    <td>{{ dep.fromLabel }}</td>
                    <td>{{ dep.ref.label }} → {{ dep.targetLabel }}</td>
                    <td>
                      <select (change)="choose(dep.key, $any($event.target).value)">
                        <option value="detacher" [selected]="dep.resolution.action === 'detacher'">Retirer le lien</option>
                        @if (dep.ref.mode === 'id') {
                          <option value="supprimer" [selected]="dep.resolution.action === 'supprimer'">Supprimer aussi cet objet</option>
                          @for (a of dep.alternatives; track a.uid) {
                            <option [value]="'r:' + a.uid" [selected]="isReassigned(dep.resolution, a.uid)">Rattacher à {{ a.label }}</option>
                          }
                        }
                      </select>
                    </td>
                  </tr>
                }
              </tbody>
            </table>
          }
          @for (w of p.warnings; track w) { <p class="warn-box small">{{ w }}</p> }
          <p class="muted small">L'opération peut être annulée (Ctrl+Z).</p>
          <div class="modal-actions">
            <button (click)="done.emit(false)">Annuler</button>
            <button class="danger primary" (click)="confirm()">Supprimer {{ p.deleted.length > 1 ? 'les ' + p.deleted.length + ' éléments' : '' }}</button>
          </div>
        }
      </div>
    </div>
  `,
})
export class DeleteDialogComponent {
  readonly uid = input.required<string>();
  readonly done = output<boolean>();
  private readonly svc = inject(DossierService);
  private readonly choices = signal<Record<string, Resolution>>({});
  protected readonly error = signal<string | null>(null);

  protected readonly plan = computed<DeletionPlan | null>(() => {
    const d = this.svc.dossier();
    this.svc.revision();
    if (!d) return null;
    try {
      return planDeletion(d, this.uid(), this.choices());
    } catch (e) {
      this.error.set(e instanceof Error ? e.message : String(e));
      return null;
    }
  });

  protected choose(key: string, value: string): void {
    const r: Resolution = value.startsWith('r:') ? { action: 'reaffecter', targetUid: value.slice(2) } : { action: value as 'detacher' | 'supprimer' };
    this.choices.update((c) => ({ ...c, [key]: r }));
  }

  protected isReassigned(r: Resolution, uid: string): boolean {
    return r.action === 'reaffecter' && r.targetUid === uid;
  }

  protected confirm(): void {
    const n = this.plan()?.deleted.length ?? 0;
    const ok = this.svc.run((d) => executeDeletion(d, this.uid(), this.choices()), `${n} élément(s) supprimé(s).`);
    this.done.emit(ok !== undefined || n > 0);
  }
}
