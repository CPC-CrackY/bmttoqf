import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CoreAdminImportsComponent } from './core-admin-imports.component';
import { CoreAdminImportsModalImportFileComponent } from './core-admin-imports-modal-import-file/core-admin-imports-modal-import-file.component';
import { FormsModule } from '@angular/forms';
import { DirectivesModule } from '../../../directives/directives.module';

@NgModule({
  declarations: [CoreAdminImportsComponent, CoreAdminImportsModalImportFileComponent],
  imports: [
    CommonModule,
    FormsModule,
    DirectivesModule
  ]
})
export class CoreAdminImportsModule { }
