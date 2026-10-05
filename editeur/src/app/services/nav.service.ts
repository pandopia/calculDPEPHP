import { Injectable, signal } from '@angular/core';
import { TabKey } from '../core/metier/catalog';
import { Model } from '../core/metier/model';
import { Issue } from '../core/validation/issues';

/**
 * Navigation stable dans le dossier, reflétée dans l'URL (#/onglet/section/uid)
 * pour que précédent/suivant du navigateur fonctionnent.
 */
export interface NavState {
  tab: TabKey;
  section: string | null;
  uid: string | null;
  field: string | null;
}

@Injectable({ providedIn: 'root' })
export class NavService {
  readonly state = signal<NavState>({ tab: 'synthese', section: null, uid: null, field: null });
  /** incrémenté à chaque demande de focus sur un champ */
  readonly focusTick = signal(0);

  constructor() {
    window.addEventListener('hashchange', () => this.fromHash());
    this.fromHash();
  }

  go(tab: TabKey, section: string | null = null, uid: string | null = null, field: string | null = null): void {
    this.state.set({ tab, section, uid, field });
    if (field) this.focusTick.update((n) => n + 1);
    const hash = '#/' + [tab, section, uid].filter((x) => x).join('/') + (field ? `?champ=${encodeURIComponent(field)}` : '');
    if (location.hash !== hash) history.pushState(null, '', hash);
  }

  /** Ouvre l'objet (et le champ) concerné, dans son onglet et sa section. */
  goObject(model: Model, uid: string | null, field: string | null = null): boolean {
    const o = uid ? model.objects.get(uid) : undefined;
    if (!o) return false;
    this.go(o.tab, o.section, o.uid, field);
    return true;
  }

  goIssue(model: Model, i: Issue): void {
    if (i.nav) {
      this.go(i.nav.tab as TabKey, i.nav.section, null, null);
      return;
    }
    if (!this.goObject(model, i.uid, i.field ?? null)) this.go('controles');
  }

  reset(): void {
    this.state.set({ tab: 'synthese', section: null, uid: null, field: null });
    history.pushState(null, '', '#/');
  }

  private fromHash(): void {
    const m = /^#\/([^/?]+)?(?:\/([^/?]+))?(?:\/([^/?]+))?(?:\?champ=(.*))?$/.exec(location.hash);
    if (!m || !m[1]) return;
    this.state.set({ tab: m[1] as TabKey, section: m[2] ?? null, uid: m[3] ?? null, field: m[4] ? decodeURIComponent(m[4]) : null });
  }
}
