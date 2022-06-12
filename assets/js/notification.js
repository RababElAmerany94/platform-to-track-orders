/*
*
*  Push Notifications codelab
*  Copyright 2015 Google Inc. All rights reserved.
*
*  Licensed under the Apache License, Version 2.0 (the "License");
*  you may not use this file except in compliance with the License.
*  You may obtain a copy of the License at
*
*      https://www.apache.org/licenses/LICENSE-2.0
*
*  Unless required by applicable law or agreed to in writing, software
*  distributed under the License is distributed on an "AS IS" BASIS,
*  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*  See the License for the specific language governing permissions and
*  limitations under the License
*
*/

/* eslint-env browser, es6 */

"use strict";

// const subscriptionBtn = document.querySelector('.js-push-btn');
const applicationServerPublicKey = "BJ4VgIMZnNqEHG6auo8xTlvPkdp8NymRmORcvfujMZZPZITwg69LlOZNchp00HdPhaf8JLoYzec1mNbt-gyWHj8";
const applicationServerKey = urlBase64ToUint8Array(applicationServerPublicKey);
const subscriptionBtn = document.querySelector("#subscription-btn");
const subscriptionIndicator = document.querySelector("#subscription-indicator");
const subscriptionIndicatorText = document.querySelector("#subscription-indicator-text");
const pushSubscriptionButton = document.querySelector("#subscription-notification-button");

let swRegistration = null;
let isSubscribed = false;
let isPushEnabled = false;


function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, "+")
        .replace(/_/g, "/");

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}


document.addEventListener("DOMContentLoaded", () => {
    if (!("serviceWorker" in navigator)) {
        console.warn("Service workers are not supported by this browser");
        // changePushButtonState('incompatible');
        return;
    }

    if (!('PushManager' in window)) {
        console.warn('Push notifications are not supported by this browser');
        // changePushButtonState('incompatible');
        return;
    }

    if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
        console.warn('Notifications are not supported by this browser');
        // changePushButtonState('incompatible');
        return;
    }

    // Check the current Notification permission.
    if (Notification.permission === 'default') {
        console.warn('Notifications are denied by the user');
        handleSubscriptionBtn();
    }

    // If its denied, the button should appears as such, until the user changes the permission manually
    if (Notification.permission === 'denied') {
        console.warn('Notifications are denied by the user');
        handleSubscriptionBtn();

        return;
    }

    navigator.serviceWorker.register('/service-worker.js')
        .then(function (swReg) {
            console.log('Service Worker is registered', swReg);

            swRegistration = swReg;
            initializeUI();
            push_updateSubscription();
        });

    if (subscriptionBtn) {
        subscriptionBtn.addEventListener('click', function () {
            console.log('handleSubscriptionBtnClick');
            handleSubscriptionBtnClick();
        });
    }

    function initializeUI() {
        // Set the initial subscription value
        swRegistration.pushManager.getSubscription()
            .then(function (subscription) {
                console.log('subscription', subscription);

                isSubscribed = (subscription !== null);

                if (isSubscribed) {
                    console.log('User IS subscribed.');
                } else {
                    console.log('User is NOT subscribed.');
                }

                handleSubscriptionBtn();
            });
    }

    function handleSubscriptionBtnClick() {
        if (!isSubscribed) {
            subscribeUser();
        } else {
            unsubscribeUser();
        }
    }

    function handleSubscriptionBtn() {
        console.log('subscriptionBtn', subscriptionBtn);
        if (subscriptionBtn) {
            console.log('Notification.permission', Notification.permission);

            if (Notification.permission === 'denied') {
                subscriptionBtn.textContent = 'Push Notification Blocked.';
                subscriptionIndicatorText.textContent = 'Push Notification Blocked';
                subscriptionIndicator.style.backgroundColor = '#cc2103';
                subscriptionBtn.disabled = true;
            }

            if (isSubscribed) {
                subscriptionBtn.textContent = 'Disable Push Notification';
                subscriptionIndicatorText.textContent = 'Push Notification Enabled';
                subscriptionIndicator.style.backgroundColor = '#00CC00';
            } else {
                subscriptionBtn.textContent = 'Enable Push Notification';
                subscriptionIndicatorText.textContent = 'Push Notification Disabled';
                subscriptionIndicator.style.backgroundColor = '#73879C';
            }

            subscriptionBtn.disabled = false;
        }
    }

    function subscribeUser() {
        swRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
        })
            .then(function (subscription) {
                console.log('subscription', subscription);

                console.log('User is subscribed.');

                // updateSubscriptionOnServer(subscription);

                // Keep your server in sync with the latest endpoint
                push_updateSubscription();

                isSubscribed = true;

                handleSubscriptionBtn();
            })
            .catch(function (err) {
                console.error('Failed to subscribe the user: ', err);

                handleSubscriptionBtn();
            });
    }

    function unsubscribeUser() {
        swRegistration.pushManager.getSubscription()
            .then(function (subscription) {
                if (subscription) {
                    let endpoint = subscription.endpoint;

                    subscription.unsubscribe()
                        .then((result) => {
                            if (result) {
                                var headers = new Headers();
                                var body = JSON.stringify({
                                    _METHOD: "DELETE",
                                    endpoint: endpoint,
                                });
                                var init = {
                                    method: "POST",
                                    headers: headers,
                                    // mode: 'cors',
                                    // cache: 'default'
                                    body: body
                                };

                                fetch("/notification", init)
                                    .then(function (response) {
                                        return response.text();
                                    })
                                    .then(function (text) {
                                        console.log("Request successful", text);
                                    })
                                    .catch(function (error) {
                                        console.log("Request failed", error);
                                    });
                            }
                        });
                    return;
                }
            })
            .catch(function (error) {
                console.error("Error unsubscribing", error);
            })
            .then(function () {
                console.log('User is unsubscribed.');
                isSubscribed = false;

                handleSubscriptionBtn();
            });
    }

    if (!pushSubscriptionButton) {
        return;
    }

    pushSubscriptionButton.addEventListener('click', function () {
        if (isPushEnabled) {
            push_unsubscribe();
        } else {
            push_subscribe();
        }
    });

    function push_subscribe() {
        // changePushButtonState('computing');

        navigator.serviceWorker.ready
            .then(serviceWorkerRegistration => {
                serviceWorkerRegistration.pushManager
                    .subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: applicationServerKey
                    })
                    .then((subscription) => {
                        // Subscription was successful
                        // create subscription on your server
                        return push_sendSubscriptionToServer(subscription, 'POST');
                    })
                    // .then((subscription) => {
                    //     subscription && changePushButtonState('enabled');
                    // }) // update your UI
                    .catch((e) => {
                        if (Notification.permission === 'denied') {
                            // The user denied the notification permission which
                            // means we failed to subscribe and the user will need
                            // to manually change the notification permission to
                            // subscribe to push messages
                            console.warn('Notifications are denied by the user.');
                            // changePushButtonState('incompatible');
                        } else {
                            // A problem occurred with the subscription; common reasons
                            // include network errors or the user skipped the permission
                            console.error('Impossible to subscribe to push notifications', e);
                            // changePushButtonState('disabled');
                        }
                    });
            });
    }

    function push_updateSubscription() {
        navigator.serviceWorker.ready
            .then(serviceWorkerRegistration => {
                serviceWorkerRegistration.pushManager
                    .getSubscription()
                    .then((subscription) => {
                        // changePushButtonState('disabled');

                        if (!subscription) {
                            // We aren't subscribed to push, so set UI to allow the user to enable push
                            return;
                        }

                        // Keep your server in sync with the latest endpoint
                        return push_sendSubscriptionToServer(subscription, 'PUT');
                    })
                    .then((subscription) => {
                        // subscription && changePushButtonState('enabled');
                    }) // Set your UI to show they have subscribed for push messages
                    .catch(e => {
                        console.error('Error when updating the subscription', e);
                    });
            })
            .catch(e => {
                console.error('Error when getting the subscription', e);
            });
    }

    function push_sendSubscriptionToServer(subscription, method) {
        const subscriptionJson = JSON.parse(JSON.stringify(subscription));

        var headers = new Headers();
        var body = JSON.stringify({
            _METHOD: method,
            endpoint: subscription.endpoint,
            p256dh: subscriptionJson.keys.p256dh,
            auth: subscriptionJson.keys.auth
        });
        var init = {
            method: 'POST',
            headers: headers,
            // mode: 'cors',
            // cache: 'default'
            body: body
        };

        return fetch('/notification', init)
            .then(function (response) {
                return response.text();
            })
            .then(function (text) {
                console.log('Request successful', text);
            })
            .catch(function (error) {
                console.log('Request failed', error);
            });
    }
});
