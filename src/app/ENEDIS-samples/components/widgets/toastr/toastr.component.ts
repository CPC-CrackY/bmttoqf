import { Component, OnInit } from '@angular/core';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'app-toastr',
  templateUrl: './toastr.component.html',
  styleUrls: ['./toastr.component.scss']
})
export class ToastrComponent implements OnInit {

  toastrOptions = {
    closeButton: true,
    timeOut: 5000,
    extendedTimeOut: 5000,
    enableHtml: true,
    progressBar: true,
    // progressAnimation: 'decreasing',
    positionClass: 'toast-top-right',
    tapToDismiss: true
  };

  constructor(private toastr: ToastrService) { }

  showInfo() {
    this.toastr.info('Ceci est une <b>information</b>.', 'Notification !', this.toastrOptions);
  }
  showSuccess() {
    this.toastr.success('Ceci est une <b>réussite</b>.', 'Notification !', this.toastrOptions);
  }
  showWarning() {
    this.toastr.warning('Ceci est un <b>avertissement</b>.', 'Notification !', this.toastrOptions);
  }
  showDanger() {
    this.toastr.error('Ceci est une <b>erreur ou  un danger</b>.', 'Notification !', this.toastrOptions);
  }
  showOptions() {
    this.toastr.show('Ceci est une <b>erreur ou  un danger</b>.', 'Notification !', {
      closeButton: true,
      timeOut: 5000,
      extendedTimeOut: 5000,
      enableHtml: true,
      progressBar: true,
      progressAnimation: 'decreasing',
      positionClass: 'toast-top-right',
      tapToDismiss: true
    });
  }

  ngOnInit() {
  }

}
