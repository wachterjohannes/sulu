// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {sidebarViewRegistry} from 'sulu-admin-bundle/containers/Sidebar';
import Preview from './views/Preview';

sidebarViewRegistry.add('preview', Preview);

bundleReady();
