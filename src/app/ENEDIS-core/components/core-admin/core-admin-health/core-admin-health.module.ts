import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CoreAdminHealthComponent } from './core-admin-health.component';
import { GaugeModule } from '../../gauge/gauge.module';

@NgModule({
    declarations: [CoreAdminHealthComponent],
    imports: [
        CommonModule,
        GaugeModule
    ]
})
export class CoreAdminHealthModule { }
