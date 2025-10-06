import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { NgSelectModule } from '@ng-select/ng-select';
import { NgxEchartsModule } from 'ngx-echarts';
import * as echarts from 'echarts';

import { DirectivesModule } from '../../directives/directives.module';
import { CoreAdminRoutingModule } from './core-admin-routing.module';
import { SecondaryNavbarsModule } from '../navbar/secondary-navbars/secondary-navbars.module';

import { CoreAdminImportsModule } from './core-admin-imports/core-admin-imports.module';
import { CoreAdminComponent } from './core-admin.component';
import { CoreAdminModalUserComponent } from './core-admin-modal-user/core-admin-modal-user.component';
import { CoreAdminModalAddUserComponent } from './core-admin-modal-add-user/core-admin-modal-add-user.component';
import { CoreAdminRolesListComponent } from './core-admin-roles-list/core-admin-roles-list.component';
import { CoreAdminUsersListComponent } from './core-admin-users-list/core-admin-users-list.component';
import { ModalModule } from 'ngx-bootstrap/modal';
import { CoreAdminParametersListComponent } from './core-admin-parameters-list/core-admin-parameters-list.component';
import { GaugeModule } from '../gauge/gauge.module';
import { CoreAdminHealthModule } from './core-admin-health/core-admin-health.module';
import { CoreAdminDbSyncComponent } from './core-admin-db-sync/core-admin-db-sync.component';
import { CoreAdminModalTableAnonymisationComponent } from './core-admin-db-sync/core-admin-modal-table-anonymisation/core-admin-modal-table-anonymisation.component';

@NgModule({
  declarations: [
    CoreAdminComponent,
    CoreAdminModalUserComponent,
    CoreAdminModalAddUserComponent,
    CoreAdminRolesListComponent,
    CoreAdminUsersListComponent,
    CoreAdminParametersListComponent,
    CoreAdminDbSyncComponent,
    CoreAdminModalTableAnonymisationComponent,
  ],
  imports: [
    CommonModule,
    FormsModule,
    NgSelectModule,
    ModalModule.forChild(),
    NgxEchartsModule.forRoot({ echarts }),
    SecondaryNavbarsModule,
    DirectivesModule,

    CoreAdminRoutingModule,
    CoreAdminImportsModule,
    CoreAdminHealthModule,
  ],
  exports: [
    SecondaryNavbarsModule,
    DirectivesModule,
    GaugeModule
  ]
})
export class CoreAdminModule { }
