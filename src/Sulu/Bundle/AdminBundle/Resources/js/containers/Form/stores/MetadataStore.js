// @flow
import resourceMetadataStore from '../../../stores/ResourceMetadataStore';
import type {Schema, SchemaTypes} from '../types';

class MetadataStore {
    getSchemaTypes(resourceKey: string): Promise<SchemaTypes> {
        return resourceMetadataStore.loadConfiguration(resourceKey)
            .then((configuration) => {
                const {types} = configuration;

                if (!types) {
                    return {};
                }

                const schemaTypes = {};
                Object.keys(types).forEach((key) => {
                    schemaTypes[key] = {
                        key: key,
                        title: types[key].title || key,
                    };
                });

                return schemaTypes;
            });
    }

    getSchema(resourceKey: string, type: ?string): Promise<Schema> {
        return resourceMetadataStore.loadConfiguration(resourceKey)
            .then((configuration) => {
                if (configuration.types && !type) {
                    throw new Error(
                        'The "' + resourceKey + '" configuration requires a type for loading the form schema'
                    );
                }

                if (!configuration.types && type) {
                    throw new Error(
                        'The "' + resourceKey + '" configuration does not support types,'
                        + ' but a type of "' + type + '" was given'
                    );
                }

                const typeConfiguration = configuration.types ? configuration.types[type] : configuration;

                if (!('form' in typeConfiguration)) {
                    let errorMessage = 'There is no form schema for the resourceKey "' + resourceKey + '"';
                    if (type) {
                        errorMessage += ' for the type "' + type + '"';
                    }

                    throw new Error(errorMessage);
                }

                return typeConfiguration.form;
            });
    }
}

export default new MetadataStore();
