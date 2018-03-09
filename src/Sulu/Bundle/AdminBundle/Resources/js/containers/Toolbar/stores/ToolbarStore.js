// @flow
import {action, computed, observable} from 'mobx';
import type {Button, Select, ToolbarConfig, ToolbarItem} from '../types';

export default class ToolbarStore {
    @observable config: ToolbarConfig = {};

    @action setConfig(config: ToolbarConfig) {
        this.config = config;
    }

    @action clearConfig() {
        this.config = {};
    }

    @computed get disableAll(): boolean {
        return !!this.config.disableAll;
    }

    @computed get empty(): boolean {
        return !this.hasBackButtonConfig()
            && !this.hasIconsConfig()
            && !this.hasItemsConfig()
            && !this.hasLocaleConfig();
    }

    hasBackButtonConfig(): boolean {
        return !!this.config.backButton;
    }

    getBackButtonConfig(): ?Button {
        return this.config.backButton || null;
    }

    hasItemsConfig(): boolean {
        return !!this.config.items && !!this.config.items.length;
    }

    getItemsConfig(): Array<ToolbarItem> {
        return this.config.items || [];
    }

    hasIconsConfig(): boolean {
        return !!this.config.icons && !!this.config.icons.length;
    }

    getIconsConfig(): Array<string> {
        return this.config.icons || [];
    }

    hasLocaleConfig(): boolean {
        return !!this.config.locale;
    }

    getLocaleConfig(): ?Select {
        return this.config.locale;
    }
}
