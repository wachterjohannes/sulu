// @flow
import type {ComponentType} from 'react';

export type SidebarViewOptions = {};

export type SidebarView = ComponentType<SidebarViewOptions>;

export type Sizes = 'small' | 'middle' | 'large';

export type SidebarConfig = {
    view?: string,
    props?: Object,
    size?: Sizes,
};

const DEFAULT_SIZE = 'middle';

export {DEFAULT_SIZE};
