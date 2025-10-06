import { Component, OnInit, OnDestroy } from '@angular/core';
import { LoaderService } from '../../services/loader.service';

@Component({
  selector: 'spinner',
  templateUrl: './spinner.component.html',
  styleUrls: ['./spinner.component.scss']
})
export class SpinnerComponent implements OnInit, OnDestroy {
  ngOnInit(): void {
  }

  ngOnDestroy(): void {
    // this.loaderService.isLoading.unsubscribe();
  }

  loading: boolean = false;

  constructor(private loaderService: LoaderService) {
    /**
     * On subscribe à isLoading du service Loader pour s'assurez de l'état des requêtes
     */
    this.loaderService.isLoading.subscribe((v) => {
      this.loading = v;
    })
  }

}
