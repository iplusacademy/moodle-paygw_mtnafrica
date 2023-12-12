// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This module is responsible for MTN Africa content in the gateways modal.
 *
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from './repository';
import Ajax from 'core/ajax';
import Config from 'core/config';
import Log from 'core/log';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';

/**
 * Creates and shows a modal that contains a placeholder.
 *
 * @returns {Promise<Modal>}
 */
const showModalWithPlaceholder = async() => {
    const modal = await ModalFactory.create({
        body: await Templates.render('paygw_mtnafrica/placeholder', {})
    });
    modal.show();
    return modal;
};

/**
 * Process the payment.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @param {string} description Description of the payment
 * @returns {Promise<string>}
 */
export const process = (component, paymentArea, itemId, description) => {
    return Promise.all([
        showModalWithPlaceholder(),
        Repository.getConfigForJs(component, paymentArea, itemId),
    ])
    .then(([modal, mtnConfig]) => {
        modal.setTitle(getString('pluginname', 'paygw_mtnafrica'));
        console.log(description);  // eslint-disable-line
        const phoneNumber = modal.getRoot().find('#mtn-phone');
        phoneNumber.append('<h4>' + mtnConfig.phone + '</h4>');
        const userCountry = modal.getRoot().find('#mtn-country');
        userCountry.append('<h4>' + mtnConfig.usercountry + '</h4>');
        const extraDiv = modal.getRoot().find('#mtn-extra');
        extraDiv.append('<h4>' + mtnConfig.cost + ' ' + mtnConfig.currency + '</h4>');
        modal.getRoot().on(ModalEvents.hidden, () => {
            // Destroy when hidden.
            console.log('Destroy modal');  // eslint-disable-line
            modal.destroy();
        });
        return Promise.all([modal, mtnConfig]);
    })
    .then(([modal, mtnConfig]) => {
        var cancelButton = modal.getRoot().find('#mtn-cancel');
        cancelButton.on('click', function() {
            modal.destroy();
        });
        var payButton = modal.getRoot().find('#mtn-pay');
        payButton.removeAttr('disabled');
        payButton.on('click', function(e) {
            e.preventDefault();
            Promise.all([
                Repository.transactionStart(component, paymentArea, itemId),
            ])
            .then(([mtnPay]) => {
                const transId = mtnPay.transactionid;
                modal.setBody(Templates.render('paygw_mtnafrica/busy', {
                    "sesskey": Config.sesskey,
                    "phone": mtnConfig.phone,
                    "country": mtnConfig.country,
                    "component": component,
                    "paymentarea": paymentArea,
                    "transactionid": transId,
                    "itemid": itemId,
                    "description": description,
                    "reference": mtnConfig.reference,
                }));
                cancelButton = modal.getRoot().find('#mtn-cancel');
                cancelButton.on('click', function() {
                    e.preventDefault();
                    modal.destroy();
                });
                payButton = modal.getRoot().find('#mtn-pay');
                payButton.on('click', function() {
                    modal.destroy();
                });
                payButton.attr('disabled',  '');
                console.log('mtn Africa payment process started');  // eslint-disable-line
                console.log('Reference id: ' + transId);  // eslint-disable-line
                var arrayints = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
                var interval = mtnConfig.timeout;
                var cont = true;
                const b = '</div>';
                arrayints.forEach(function(el, index) {
                    setTimeout(function() {
                        if (cont == true) {
                            var progressDiv = modal.getRoot().find('#mtn-progress_bar');
                            progressDiv.attr('value', el * 10);
                            if (mtnPay.xreferenceid != '') {
                                Ajax.call([{
                                    methodname: "paygw_mtnafrica_transaction_complete",
                                    args: {
                                        component,
                                        paymentarea: paymentArea,
                                        itemid: itemId,
                                        xreferenceid: transId,
                                    },
                                    done: function(mtnPing) {
                                        modal.setFooter(el + '/10 ' + mtnPing.message);
                                        console.log(el + '/10 ' + mtnPing.message);  // eslint-disable-line
                                        var spinnerDiv = modal.getRoot().find('#mtn-spinner');
                                        if (mtnPing.success) {
                                            if (mtnPing.message == 'SUCCESSFUL') {
                                                cont = false;
                                                progressDiv.attr('value', 100);
                                                spinnerDiv.attr('style', 'display: none;');
                                                var cancelButton = modal.getRoot().find('#mtn-cancel');
                                                cancelButton.attr('style', 'display: none;');
                                                var payButton = modal.getRoot().find('#mtn-pay');
                                                payButton.removeAttr('disabled');
                                                modal.setFooter('Transaction '+ transId + ' Succes');
                                                payButton.on('click', function() {
                                                    const loc = window.location.href;
                                                    window.location.replace(loc);
                                                });
                                            }
                                        } else {
                                            cont = false;
                                            const a = '<br/><div class="p-3 mb-2 bg-danger text-white font-weight-bold">';
                                            var outDiv = modal.getRoot().find('#mtn-out');
                                            outDiv.append(a + mtnPing.message + b);
                                            spinnerDiv.attr('style', 'display: none;');
                                            return;
                                        }
                                    },
                                    fail: function(e) {
                                        console.log(getString('failed', 'paygw_mtnafrica'));  // eslint-disable-line
                                        Log.debug(e);
                                    }
                                }]);
                                if (el > 9) {
                                    modal.destroy();
                                }
                            }
                        }
                    }, index * interval);
                });
                return new Promise(() => null);
            }).catch(function() {
                modal.setBody(getString('unable', 'paygw_mtnafrica'));
                console.log('Unable to connect to MTN');  // eslint-disable-line
            });
        });
        return new Promise(() => null);
    }).catch(e => {
        Log.debug('Global error.');
        Log.debug(e);
        return Promise.reject(e.message);
    });
};
