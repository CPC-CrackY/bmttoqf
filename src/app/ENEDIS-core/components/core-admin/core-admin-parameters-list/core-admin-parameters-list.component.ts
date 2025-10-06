import { Component, OnInit } from '@angular/core';
import { ApiAzurService } from '../../../services/api-azur.service';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'app-core-admin-parameters-list',
  templateUrl: './core-admin-parameters-list.component.html',
  styleUrls: ['./core-admin-parameters-list.component.scss']
})
export class CoreAdminParametersListComponent implements OnInit {

  parameters: any[] = [];
  label = '';
  description = '';

  constructor(private apiAzurService: ApiAzurService, private toastrService: ToastrService) { }

  ngOnInit(): void {
    this.loadParameters();
  }

  async loadParameters() {
    await this.apiAzurService.get<any>(`getParameters`).then(data => {
      this.parameters = data;
    });
  }

  save(): void {
    const postFields = {
      'subject': 'saveParameters',
      parameters: this.parameters
    };
    this.apiAzurService.post<any>(postFields).then(() => {
      this.loadParameters();
    });

  }

}
