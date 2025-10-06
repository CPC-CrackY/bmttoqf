import { Component, OnInit } from '@angular/core';
import { BsModalRef, BsModalService } from 'ngx-bootstrap/modal';
import { FileImport } from '../../../components/models/file-imports';
import { ApiAzurService } from '../../../services/api-azur.service';
import { CoreAdminImportsModalImportFileComponent } from './core-admin-imports-modal-import-file/core-admin-imports-modal-import-file.component';

@Component({
  selector: 'app-core-admin-imports',
  templateUrl: './core-admin-imports.component.html',
  styleUrls: ['./core-admin-imports.component.scss']
})
export class CoreAdminImportsComponent implements OnInit {

  lastFileImports: FileImport[] = [];

  constructor(private apiAzurService: ApiAzurService, private bsModalService: BsModalService) { }

  ngOnInit(): void {
    this.updateTable();
  }

  updateTable() {
    this.apiAzurService.getOnce<FileImport[]>('lastFileImports').then((lastFileImports: FileImport[]) => { this.lastFileImports = lastFileImports });
  }

  onClickImportFile() {
    this.openImportModal();
  }

  openImportModal() {
    const bsModalRef: BsModalRef<CoreAdminImportsModalImportFileComponent> = this.bsModalService.show(CoreAdminImportsModalImportFileComponent, { class: 'modal-lg' });
    bsModalRef.content?.onClose.subscribe(() => this.updateTable());
  }

  export(fileImport: FileImport) {
  }

}
