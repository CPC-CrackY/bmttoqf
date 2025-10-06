import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AlertsComponent } from './alerts/alerts.component';
import { ButtonsComponent } from './buttons/buttons.component';
import { ChronologiqueComponent } from './chronologique/chronologique.component';
import { CollapseComponent } from './collapse/collapse.component';
import { FlexgridComponent } from './flexgrid/flexgrid.component';
import { ModalComponent } from './modal/modal.component';
import { ProgressComponent } from './progress/progress.component';
import { SlidersComponent } from './sliders/sliders.component';
import { TabsComponent } from './tabs/tabs.component';
import { ToastrComponent } from './toastr/toastr.component';
import { TooltipsComponent } from './tooltips/tooltips.component';
import { TypographyComponent } from './typography/typography.component';
import { WidgetsComponent } from './widgets.component';
import { CanActivateService } from '../../../ENEDIS-core/services/can-activate.service';

const routes: Routes = [
  {
    path: 'widgets',
    data: { bigTitle: 'Exemples de composants' },
    component: WidgetsComponent,
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'alerts' },
      { path: 'alerts', component: AlertsComponent, data: { title: 'Alertes' } },
      { path: 'buttons', component: ButtonsComponent, data: { title: 'Boutons' } },
      { path: 'chronologie', component: ChronologiqueComponent, data: { title: 'Chronologique' } },
      { path: 'collapse', component: CollapseComponent, data: { title: 'Collapse' } },
      { path: 'grille', component: FlexgridComponent, data: { title: 'Grille' } },
      { path: 'modal', component: ModalComponent, data: { title: 'Fenêtres modales' } },
      { path: 'progress', component: ProgressComponent, canActivate: [CanActivateService], data: { auth: ['HTA_WRITE', 'HABILITATIONS'], title: 'Barres de progression' } },
      { path: 'sliders', component: SlidersComponent, data: { title: 'Glissières' } },
      { path: 'tabs', component: TabsComponent, data: { title: 'Tabulations' } },
      { path: 'toastr', component: ToastrComponent, data: { title: 'Toasters' } },
      { path: 'tooltips', component: TooltipsComponent, data: { title: 'Tootips' } },
      { path: 'typography', component: TypographyComponent, data: { title: 'Typographie' } },
      { path: 'prohibiten', component: TypographyComponent, canActivate: [CanActivateService], data: { auth: ['X Æ A-Xii'], title: 'This is prohibiten' } },
    ]
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class WidgetsRoutingModule { }
