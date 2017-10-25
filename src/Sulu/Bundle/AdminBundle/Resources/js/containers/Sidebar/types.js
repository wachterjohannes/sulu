// @flow
import type {ComponentType} from 'react';

export type SidebarViewOptions = {};

export type SidebarView = ComponentType<SidebarViewOptions>;

export type Sizes = 'small' | 'medium' | 'large';

export type SidebarConfig = {
    view?: string,
    props?: Object,
    sizes?: Array<string>,
    defaultSize?: Sizes,
};

const DEFAULT_SIZE = 'medium';
const SIZES = ['small', 'medium', 'large'];

export {DEFAULT_SIZE, SIZES};
