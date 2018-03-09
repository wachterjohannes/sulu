// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {observable} from 'mobx';
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
        const {
            router: {
                attributes: {
                    id,
                },
            },
            route: {
                options: {
                    resourceKey,
                    locales,
                },
            },
        } = this.props;

        const options = {};
        if ((typeof locales === 'boolean' && locales === true) || (Array.isArray(locales) && locales.length > 0)) {
            options.locale = observable();
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
                <Loader/>
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

export default withSidebar(ResourceTabs, function () {
    const {
        router,
        route: {
            options: {
                preview,
            },
        },
    } = this.props;

    if (!preview) {
        return {};
    }

    return {
        view: 'preview',
        props: {
            resourceStore: this.resourceStore,
            router: router,
        },
        sizes: ['large', 'medium'],
    };
});
