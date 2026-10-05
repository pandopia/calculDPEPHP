import { AfterViewInit, Component, computed, effect, ElementRef, inject, OnDestroy, signal, ViewChild } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { Adjacence, buildMaquette, FacadeKey, Maquette } from '../core/metier/maquette';
import type * as THREE from 'three';

const COLORS: Record<Adjacence, number> = {
  exterieur: 0xe8dcc4,
  lnc: 0xb9c0cc,
  ets: 0xbfe0c9,
  sol: 0x9c8f7c,
  autre: 0xd4c8ef,
};
const GLASS = 0x5b9bd5;
const DOOR = 0x8a5a3c;

const LEGEND: { label: string; color: number }[] = [
  { label: 'Mur sur l\'extérieur', color: COLORS.exterieur },
  { label: 'Sur local non chauffé', color: COLORS.lnc },
  { label: 'Sur espace tampon', color: COLORS.ets },
  { label: 'Autre usage / mitoyen', color: COLORS.autre },
  { label: 'Baie vitrée', color: GLASS },
  { label: 'Porte', color: DOOR },
];

/**
 * Maquette 3D approximative (three.js, chargé à la demande).
 * Un clic sur un élément ouvre sa fiche.
 */
@Component({
  selector: 'app-maquette-3d',
  template: `
    <section class="card maquette">
      <div class="maquette-head">
        <h2>Maquette approximative <span class="muted small">{{ subtitle() }}</span></h2>
        <div class="maquette-tools">
          <button class="small" (click)="resetView()">Recentrer</button>
        </div>
      </div>
      @if (!maquette()) {
        <p class="muted">Pas assez de données (murs ou planchers) pour esquisser le volume.</p>
      }
      <div class="maquette-view" #host [class.hidden]="!maquette()">
        @if (hover(); as h) { <div class="maquette-tip" [style.left.px]="h.x" [style.top.px]="h.y"><strong>{{ h.title }}</strong><br />{{ h.detail }}</div> }
        @if (error()) { <p class="error-box">{{ error() }}</p> }
        <div class="compass-badge" title="Le nord est vers le fond de la scène">N ↑</div>
      </div>
      <div class="maquette-foot">
        <div class="legend">
          @for (l of legend; track l.label) { <span><i [style.background]="hex(l.color)"></i>{{ l.label }}</span> }
        </div>
        <p class="muted small">Ordre d'idée : le DPE ne décrit que des surfaces, orientations et rattachements. Volume, position des murs et des ouvertures sont déduits, pas mesurés.
          @for (n of maquette()?.notes ?? []; track n) { {{ n }} }</p>
      </div>
    </section>
  `,
})
export class Maquette3dComponent implements AfterViewInit, OnDestroy {
  @ViewChild('host', { static: true }) private host!: ElementRef<HTMLDivElement>;
  private readonly svc = inject(DossierService);
  private readonly nav = inject(NavService);
  protected readonly legend = LEGEND;
  protected readonly hover = signal<{ x: number; y: number; title: string; detail: string } | null>(null);
  protected readonly error = signal<string | null>(null);

  protected readonly maquette = computed<Maquette | null>(() => {
    this.svc.revision();
    const d = this.svc.dossier();
    const m = this.svc.model();
    return d && m ? buildMaquette(d, m.graph) : null;
  });
  protected readonly subtitle = computed(() => {
    const m = this.maquette();
    if (!m) return '';
    const f = (n: number) => n.toLocaleString('fr-FR', { maximumFractionDigits: 0 });
    return `${m.scope === 'immeuble' ? 'immeuble' : 'logement'} · ${f(m.width)} × ${f(m.depth)} m · ${m.levels} niveau(x) · h ${f(m.height)} m`;
  });

  private three: typeof THREE | null = null;
  private renderer: THREE.WebGLRenderer | null = null;
  private scene: THREE.Scene | null = null;
  private camera: THREE.PerspectiveCamera | null = null;
  private controls: { update(): void; target: THREE.Vector3; dispose(): void } | null = null;
  private pickables: THREE.Object3D[] = [];
  private frame = 0;
  private resize?: ResizeObserver;
  private ready = false;
  private down: { x: number; y: number } | null = null;

  constructor() {
    effect(() => {
      const m = this.maquette();
      if (this.ready) this.build(m);
    });
  }

  async ngAfterViewInit(): Promise<void> {
    try {
      const [three, controls] = await Promise.all([import('three'), import('three/examples/jsm/controls/OrbitControls.js')]);
      this.three = three;
      const el = this.host.nativeElement;
      this.renderer = new three.WebGLRenderer({ antialias: true, alpha: true });
      this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
      el.appendChild(this.renderer.domElement);
      this.scene = new three.Scene();
      this.camera = new three.PerspectiveCamera(40, 1, 0.1, 5000);
      this.controls = new controls.OrbitControls(this.camera, this.renderer.domElement) as never;
      (this.controls as unknown as { enableDamping: boolean }).enableDamping = true;
      this.scene.add(new three.HemisphereLight(0xffffff, 0x8899aa, 1.1));
      const sun = new three.DirectionalLight(0xffffff, 1.4);
      sun.position.set(40, 80, 60);
      this.scene.add(sun);
      this.resize = new ResizeObserver(() => this.fit());
      this.resize.observe(el);
      this.renderer.domElement.addEventListener('pointermove', (e) => this.onMove(e));
      this.renderer.domElement.addEventListener('pointerleave', () => this.hover.set(null));
      this.renderer.domElement.addEventListener('pointerdown', (e) => (this.down = { x: e.clientX, y: e.clientY }));
      this.renderer.domElement.addEventListener('click', (e) => {
        if (this.down && Math.hypot(e.clientX - this.down.x, e.clientY - this.down.y) > 4) return;
        this.onClick(e);
      });
      this.ready = true;
      this.fit();
      this.build(this.maquette());
      const loop = () => {
        this.frame = requestAnimationFrame(loop);
        this.controls?.update();
        if (this.renderer && this.scene && this.camera) this.renderer.render(this.scene, this.camera);
      };
      loop();
    } catch (e) {
      this.error.set('Affichage 3D indisponible dans ce navigateur (WebGL).');
      console.error(e);
    }
  }

  ngOnDestroy(): void {
    cancelAnimationFrame(this.frame);
    this.resize?.disconnect();
    this.controls?.dispose();
    this.clear();
    this.renderer?.dispose();
    this.renderer?.domElement.remove();
  }

  protected hex(c: number): string {
    return '#' + c.toString(16).padStart(6, '0');
  }

  protected resetView(): void {
    const m = this.maquette();
    if (!m || !this.camera || !this.controls) return;
    const size = Math.max(m.width, m.depth, m.height);
    this.camera.position.set(size * 0.75, size * 0.5, size * 0.95);
    this.controls.target.set(0, m.height / 2.5, 0);
    this.controls.update();
  }

  private fit(): void {
    if (!this.renderer || !this.camera) return;
    const el = this.host.nativeElement;
    const w = el.clientWidth || 600;
    const h = el.clientHeight || 380;
    this.renderer.setSize(w, h);
    this.camera.aspect = w / h;
    this.camera.updateProjectionMatrix();
  }

  private clear(): void {
    if (!this.scene) return;
    for (const o of [...this.scene.children]) {
      if ((o as THREE.Light).isLight) continue;
      this.scene.remove(o);
      o.traverse((c) => {
        const mesh = c as THREE.Mesh;
        mesh.geometry?.dispose();
        const mat = mesh.material as THREE.Material | THREE.Material[] | undefined;
        if (Array.isArray(mat)) mat.forEach((x) => x.dispose());
        else mat?.dispose();
      });
    }
    this.pickables = [];
  }

  private build(m: Maquette | null): void {
    const T = this.three;
    if (!T || !this.scene) return;
    const first = this.pickables.length === 0 && this.scene.children.length <= 2;
    this.clear();
    if (!m) return;
    const { width: W, depth: D, height: H } = m;
    const group = new T.Group();
    this.scene.add(group);

    const mat = (color: number, opts: Partial<THREE.MeshStandardMaterialParameters> = {}) =>
      new T.MeshStandardMaterial({ color, roughness: 0.85, metalness: 0, side: T.DoubleSide, ...opts });
    const edges = (mesh: THREE.Mesh, color = 0x6b6156) => {
      const line = new T.LineSegments(new T.EdgesGeometry(mesh.geometry), new T.LineBasicMaterial({ color, transparent: true, opacity: 0.5 }));
      mesh.add(line);
    };
    const pick = (mesh: THREE.Object3D, uid: string, title: string, detail: string) => {
      mesh.userData = { uid, title, detail };
      this.pickables.push(mesh);
    };
    const fmt = (n: number) => n.toLocaleString('fr-FR', { maximumFractionDigits: 1 });

    // sol de référence
    const groundSize = Math.max(W, D) * 1.8;
    const ground = new T.Mesh(new T.CircleGeometry(groundSize / 2, 64), mat(0xf4f6f9, { roughness: 1 }));
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -0.05;
    group.add(ground);
    const grid = new T.GridHelper(groundSize, Math.round(groundSize / 5), 0xd5dae1, 0xe3e7ec);
    grid.position.y = -0.04;
    group.add(grid);

    // façades : repère local (u le long de la façade, v vers le haut), posé selon l'orientation
    const place = (f: FacadeKey, obj: THREE.Object3D, u: number, v: number, offset = 0) => {
      // u part du coin gauche vu de l'extérieur
      switch (f) {
        case 'sud': obj.position.set(-W / 2 + u, v, D / 2 + offset); break;
        case 'nord': obj.position.set(W / 2 - u, v, -D / 2 - offset); obj.rotation.y = Math.PI; break;
        case 'est': obj.position.set(W / 2 + offset, v, D / 2 - u); obj.rotation.y = Math.PI / 2; break;
        case 'ouest': obj.position.set(-W / 2 - offset, v, -D / 2 + u); obj.rotation.y = -Math.PI / 2; break;
      }
    };
    for (const f of ['sud', 'nord', 'est', 'ouest'] as FacadeKey[]) {
      const side = f === 'sud' || f === 'nord' ? W : D;
      const segs = m.facades[f];
      if (!segs.length) {
        // côté sans mur déclaré (mitoyenneté) : silhouette discrète
        const ghost = new T.Mesh(new T.PlaneGeometry(side, H), mat(0xffffff, { transparent: true, opacity: 0.18 }));
        place(f, ghost, side / 2, H / 2);
        edges(ghost, 0xb0b6bf);
        group.add(ghost);
        continue;
      }
      for (const s of segs) {
        const wall = new T.Mesh(new T.PlaneGeometry(Math.max(s.length, 0.01), H), mat(COLORS[s.adjacence]));
        place(f, wall, s.start + s.length / 2, H / 2);
        edges(wall);
        pick(wall, s.uid, s.title, `Mur ${f} · ${fmt(s.surface)} m²`);
        group.add(wall);
      }
    }
    // planchers bas et hauts (bandes est-ouest)
    for (const [slabs, y, color] of [[m.floors, 0, 0xcfc6b8], [m.roofs, H, 0xb8a993]] as const) {
      for (const s of slabs) {
        const slab = new T.Mesh(new T.BoxGeometry(Math.max(s.length, 0.01), 0.25, D), mat(color));
        slab.position.set(-W / 2 + s.start + s.length / 2, y, 0);
        edges(slab);
        pick(slab, s.uid, s.title, `${y ? 'Plancher haut' : 'Plancher bas'} · ${fmt(s.surface)} m²`);
        group.add(slab);
      }
    }
    // séparations de niveaux, discrètes
    for (let i = 1; i < m.levels; i++) {
      const line = new T.LineLoop(
        new T.BufferGeometry().setFromPoints([new T.Vector3(-W / 2, i * m.levelHeight, -D / 2), new T.Vector3(W / 2, i * m.levelHeight, -D / 2), new T.Vector3(W / 2, i * m.levelHeight, D / 2), new T.Vector3(-W / 2, i * m.levelHeight, D / 2)]),
        new T.LineBasicMaterial({ color: 0x9aa1ab, transparent: true, opacity: 0.35 }),
      );
      group.add(line);
    }
    // ouvertures
    const glass = mat(GLASS, { transparent: true, opacity: 0.85, roughness: 0.2, metalness: 0.1 });
    const door = mat(DOOR);
    for (const o of m.openings) {
      if (o.facade === 'toit') {
        const mesh = new T.Mesh(new T.BoxGeometry(o.w, 0.06, o.h), glass);
        mesh.position.set(-W / 2 + o.x + o.w / 2, H + 0.16, -D / 2 + o.y + o.h / 2);
        pick(mesh, o.uid, o.title, `Baie de toit · ${fmt(o.surface)} m² au total`);
        group.add(mesh);
        continue;
      }
      const mesh = new T.Mesh(new T.PlaneGeometry(o.w, o.h), o.kind === 'porte' ? door : glass);
      place(o.facade, mesh, o.x + o.w / 2, o.y + o.h / 2, 0.06);
      pick(mesh, o.uid, o.title, `${o.kind === 'porte' ? 'Porte' : 'Baie vitrée'} ${o.facade} · ${fmt(o.surface)} m² au total`);
      group.add(mesh);
    }
    // repère nord
    const arrow = new T.ArrowHelper(new T.Vector3(0, 0, -1), new T.Vector3(0, 0.1, -D / 2 - Math.max(3, D * 0.15)), Math.max(3, D * 0.2), 0x1f5fbf);
    group.add(arrow);
    if (first) this.resetView();
  }

  private ray(e: PointerEvent | MouseEvent): THREE.Intersection | null {
    const T = this.three;
    if (!T || !this.camera || !this.renderer) return null;
    const r = this.renderer.domElement.getBoundingClientRect();
    const v = new T.Vector2(((e.clientX - r.left) / r.width) * 2 - 1, -((e.clientY - r.top) / r.height) * 2 + 1);
    const rc = new T.Raycaster();
    rc.setFromCamera(v, this.camera);
    return rc.intersectObjects(this.pickables, false)[0] ?? null;
  }

  private onMove(e: PointerEvent): void {
    const hit = this.ray(e);
    const r = this.host.nativeElement.getBoundingClientRect();
    if (hit) {
      const u = hit.object.userData as { title: string; detail: string };
      this.hover.set({ x: e.clientX - r.left + 14, y: e.clientY - r.top + 14, title: u.title, detail: u.detail });
      this.renderer!.domElement.style.cursor = 'pointer';
    } else {
      this.hover.set(null);
      this.renderer!.domElement.style.cursor = 'grab';
    }
  }

  private onClick(e: MouseEvent): void {
    const hit = this.ray(e);
    const uid = (hit?.object.userData as { uid?: string } | undefined)?.uid;
    const model = this.svc.model();
    if (uid && model) this.nav.goObject(model, uid);
  }
}
