// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {configPromise} from 'sulu-admin-bundle';
import {sidebarViewRegistry} from 'sulu-admin-bundle/containers/Sidebar';
import Preview, {previewConfigStore} from './views/Preview';

configPromise.then((response) => {
    previewConfigStore.setConfig(response['sulu_preview']);

    return response;
});

sidebarViewRegistry.add('preview', Preview);

bundleReady();
