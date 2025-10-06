import { Component } from '@angular/core';
import { ToastrService } from 'ngx-toastr';
import { ApiAzurService } from '../../../services/api-azur.service';
import { BsModalRef, BsModalService } from 'ngx-bootstrap/modal';
import { CoreAdminModalTableAnonymisationComponent } from './core-admin-modal-table-anonymisation/core-admin-modal-table-anonymisation.component';

@Component({
  selector: 'app-core-admin-db-sync',
  templateUrl: './core-admin-db-sync.component.html',
  styleUrls: ['./core-admin-db-sync.component.scss']
})
export class CoreAdminDbSyncComponent {

  tables: any[] = [];
  allChecked: boolean = false;
  public webEnv: 'localhost' | 'poc' | 'dev' | 'prod' | '' = '';

  constructor(readonly apiAzurService: ApiAzurService, private bsModalService: BsModalService, readonly toastrService: ToastrService) { }

  toggleAllCheckboxes() {
    this.allChecked = !this.allChecked;
    this.tables.forEach(table => {
      table.checked = this.allChecked;
    });
  }

  updateAllChecked() {
    this.allChecked = this.tables.every(table => table.checked);
  }

  someChecked(): boolean {
    return this.tables.some(table => table.checked) && !this.allChecked;
  }

  ngOnInit(): void {
    this.webEnv =
      window.location.hostname.includes("localhost")
      ? 'localhost'
      : (window.location.hostname.includes("-poc."))
      ? 'poc'
      : (window.location.hostname.includes("-dev."))
      ? 'dev'
      : 'prod';
    this.loadTablesToExport();
  }

  async loadTablesToExport() {
    await this.apiAzurService.get<any>(`getTablesToExport`).then((tables: any[]) => {
      this.tables = tables;
    });
  }

  getAnonymizedFieldsCount(table: any): number {
    return table.fields.filter((field: any) => field.anonymize).length;
  }

  onClickEdit(table: any) {
    const initialState: any = { table: table };
    const bsModalRef: BsModalRef<CoreAdminModalTableAnonymisationComponent> = this.bsModalService.show(
      CoreAdminModalTableAnonymisationComponent,
      { initialState, class: 'modal-xl' }
    );
  }

  onClickSave() {
    const postFields = {
      subject: 'saveTablesToExport',
      tables: this.tables
    };
    this.apiAzurService.post<any>(postFields).then(() => {
      this.toastrService.show(`Les paramètres ont été mis à jour.`, `Yes !`);
    }).catch(error => {
      alert(error);
    }).finally(() => {
      // this.saving = false;
    });

  }



}
