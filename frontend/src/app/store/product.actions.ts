import { createAction, props } from '@ngrx/store';
import { Product } from '../core/api/api.models';

export const productsLoaded = createAction('[Products] Loaded', props<{ products: Product[]; total: number }>());
export const productSelected = createAction('[Products] Selected', props<{ product: Product | null }>());

