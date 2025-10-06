import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreGrantersComponent } from './core-granters.component';

describe('CoreGrantersComponent', () => {
  let component: CoreGrantersComponent;
  let fixture: ComponentFixture<CoreGrantersComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoreGrantersComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CoreGrantersComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
