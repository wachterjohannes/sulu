// @flow
import React from 'react';
import {isObservableArray, observable} from 'mobx';
import {observer} from 'mobx-react';
import Tabs from '../../components/Tabs';
import Loader from '../../components/Loader';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../utils/Translator';
import ResourceStore from '../../stores/ResourceStore';
import {withSidebar} from '../../containers/Sidebar';
import resourceTabsStyle from './resourceTabs.scss';

@observer
class ResourceTabs extends React.Component<ViewProps> {
    resourceStore: ResourceStore;

    componentWillMount() {
        const {router, route} = this.props;
        const {
            attributes: {
                id,
            },
        } = router;
        const {
            options: {
                resourceKey,
                locales,
            },
        } = route;

        const options = {};
        if (
            (typeof locales === 'boolean' && locales === true)
            || ((Array.isArray(locales) || isObservableArray(locales)) && locales.length > 0)
        ) {
            options.locale = observable.box();
        }

        this.resourceStore = new ResourceStore(resourceKey, id, options);
    }

    componentWillUnmount() {
        this.resourceStore.destroy();
    }

    handleSelect = (index: number) => {
        const {router, route} = this.props;
        router.navigate(route.children[index].name, router.attributes);
    };

    render() {
        const {children, route} = this.props;
        const ChildComponent = children ? children({resourceStore: this.resourceStore}) : null;
        const loader = (
            <div className={resourceTabsStyle.loader}>
                <Loader />
            </div>
        );

        const selectedRouteIndex = ChildComponent
            ? route.children.findIndex((childRoute) => childRoute === ChildComponent.props.route)
            : undefined;

        return (
            <div>
                <Tabs selectedIndex={selectedRouteIndex} onSelect={this.handleSelect}>
                    {route.children.map((childRoute) => {
                        const tabTitle = childRoute.options.tabTitle;
                        return (
                            <Tabs.Tab key={childRoute.name}>
                                {tabTitle ? translate(tabTitle) : childRoute.name}
                            </Tabs.Tab>
                        );
                    })}
                </Tabs>
                {(this.resourceStore.loading)
                    ? loader
                    : ChildComponent
                }
            </div>
        );
    }
}

export default withSidebar(ResourceTabs, function() {
    return {
        view: 'preview',
        sizes: ['large', 'medium'],
    };
});
