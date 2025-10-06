import { HttpClient } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { Subject } from 'rxjs';
import { FileImport } from '../../../models/file-imports';
import { ApiAzurService } from '../../../../services/api-azur.service';

@Component({
  selector: 'app-core-admin-imports-modal-import-file',
  templateUrl: './core-admin-imports-modal-import-file.component.html',
  styleUrls: ['./core-admin-imports-modal-import-file.component.scss']
})
export class CoreAdminImportsModalImportFileComponent implements OnInit {

  fileTypes: FileImport[] = [];
  selectedTable: number = 0;
  file: any;
  public onClose: Subject<any> = new Subject();

  constructor(private apiAzurService: ApiAzurService) { }

  ngOnInit(): void {
    this.apiAzurService.getOnce('importFileTypes').then((fileTypes: FileImport[]) => { this.fileTypes = fileTypes; });
  }

  onFileChange($event: any) {
    this.file = $event.target.files[0];
  }

  upload() {
    const formData = new FormData();
    formData.append("subject", 'fileUpload');
    formData.append("table", this.selectedTable.toString());
    formData.append("file", this.file);
    this.apiAzurService.post(formData).then(() => { this.onClose.next('reload') });
  }

}
