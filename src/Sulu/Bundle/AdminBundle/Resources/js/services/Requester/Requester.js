// @flow
import {isObservableArray} from 'mobx';
import type {HandleResponseHook} from './types';

const defaultOptions = {
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
};

const eventStreams = {};
const waitFor = {};

function isMercureResponse(response): boolean {
    return null !== response.headers.get('Link')
        && null !== response.headers.get('Link').match(/<([^>]+)>;\s+rel=(?:mercure|"[^"]*mercure[^"]*")/);
}

function openMercureConnection(response: Response): void {
    // Extract the hub URL and topic from the Link header
    const hubUrl = response.headers.get('Link').match(/<([^>]+)>;\s+rel=(?:mercure|"[^"]*mercure[^"]*")/)[1];
    const topic = response.headers.get('Link').match(/<([^>]+)>;\s+rel=(?:mercure|"[^"]*topic[^"]*")/)[1];

    if (eventStreams[topic]) {
        return;
    }

    // Append the topic(s) to subscribe as query parameter
    const h = new URL(hubUrl);
    h.searchParams.append('topic', topic);

    // Subscribe to updates
    eventStreams[topic] = new EventSource(h);
    eventStreams[topic].onmessage = e => {
        const data = JSON.parse(e.data);
        if (waitFor[e.lastEventId]) {
            waitFor[e.lastEventId](data);
            waitFor[e.lastEventId] = null;
        } else {
            waitFor[e.lastEventId] = data;
        }
    };
}

function handleMercureResponse(response: Response): void {
    const updateId = response.headers.get('Update');

    if (waitFor[updateId]) {
        const result = waitFor[updateId];
        waitFor[updateId] = null;

        return Promise.resolve(result);
    }

    return new Promise((resolve, reject) => {
        waitFor[updateId] = resolve;
    });
}

function transformResponseObject(data: Object) {
    return Object.keys(data).reduce((transformedData: Object, key) => {
        const value = data[key];

        if (value === null) {
            transformedData[key] = undefined;

            return transformedData;
        }

        if (Array.isArray(value)) {
            transformedData[key] = transformResponseArray(value);

            return transformedData;
        }

        if (value instanceof Object) {
            transformedData[key] = transformResponseObject(value);

            return transformedData;
        }

        transformedData[key] = value;

        return transformedData;
    }, {});
}

function transformResponseArray(data: Array<Object>) {
    return data.map((value) => {
        if (value instanceof Object) {
            return transformResponseObject(value);
        }

        return value;
    });
}

function transformRequestObject(data: Object): Object {
    return Object.keys(data).reduce((transformedData: Object, key) => {
        const value = data[key];

        if (value === undefined || value === null) {
            transformedData[key] = null;

            return transformedData;
        }

        if (Array.isArray(value) || isObservableArray(value)) {
            transformedData[key] = transformRequestArray(value);

            return transformedData;
        }

        if (value instanceof Object) {
            transformedData[key] = transformRequestObject(value);

            return transformedData;
        }

        transformedData[key] = value;

        return transformedData;
    }, {});
}

function transformRequestArray(data) {
    return data.map((value) => {
        if (Array.isArray(value) || isObservableArray(value)) {
            return transformRequestArray(value);
        }

        if (value instanceof Object) {
            return transformRequestObject(value);
        }

        return value;
    });
}

function transformRequestData(data: Object | Array<Object>) {
    if (Array.isArray(data) || isObservableArray(data)) {
        return transformRequestArray(data);
    }

    return transformRequestObject(data);
}

function handleResponse(response: Response): Promise<Object | Array<Object>> {
    for (const handleResponseHook of Requester.handleResponseHooks) {
        handleResponseHook(response);
    }

    if (!response.ok) {
        return Promise.reject(response);
    }

    if (response.status === 202) {
        return handleMercureResponse(response);
    }

    if (isMercureResponse(response)) {
        openMercureConnection(response);
    }

    if (response.status === 204) {
        // Return empty object if status code says that there is no content
        return Promise.resolve({});
    }

    return response.json().then((data) => {
        if (Array.isArray(data)) {
            return transformResponseArray(data);
        }

        return transformResponseObject(data);
    });
}

function handleObjectResponse(response: Response): Promise<Object> {
    return handleResponse(response).then((response) => {
        if (Array.isArray(response)) {
            throw Error('Response was expected to be an object, but an array was given');
        }

        return response;
    });
}

export default class Requester {
    static handleResponseHooks: Array<HandleResponseHook> = [];

    static get(url: string): Promise<Object> {
        return fetch(url, defaultOptions)
            .then(handleObjectResponse);
    }

    static post(url: string, data: ?Object): Promise<Object> {
        return fetch(
            url,
            {...defaultOptions, method: 'POST', body: data ? JSON.stringify(transformRequestData(data)) : undefined}
        ).then(handleObjectResponse);
    }

    static put(url: string, data: Object): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'PUT', body: JSON.stringify(transformRequestData(data))})
            .then(handleObjectResponse);
    }

    static patch(url: string, data: Array<Object> | Object): Promise<Array<Object> | Object> {
        return fetch(url, {...defaultOptions, method: 'PATCH', body: JSON.stringify(transformRequestData(data))})
            .then(handleResponse);
    }

    static delete(url: string): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'DELETE'})
            .then(handleObjectResponse);
    }
}
