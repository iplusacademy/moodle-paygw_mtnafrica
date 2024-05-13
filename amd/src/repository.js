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
 * MTN Africa repository module to encapsulate all of the AJAX requests that can be sent for MTN Africa.
 *
 * @copyright  Medical Access Uganda Limited (e-learning.medical-access.org)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Return the MTN Africa configuration.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @returns {Promise<{clientid: string, brandname: string, country: string, cost: number, currency: string, phone: string,
 *                    usercountry: string, timeout: number, reference: string }>}
 */
export const getConfigForJs = (component, paymentArea, itemId) => {
    const request = {
        methodname: 'paygw_mtnafrica_get_config_for_js',
        args: {
            component,
            paymentarea: paymentArea,
            itemid: itemId,
        },
    };

    return Ajax.call([request])[0];
};

/**
 * Starts an MTN Africa payment
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @returns {Promise<{transactionid: string, message: string}>}
 */
export const transactionStart = (component, paymentArea, itemId) => {
    const request = {
        methodname: 'paygw_mtnafrica_transaction_start',
        args: {
            component,
            paymentarea: paymentArea,
            itemid: itemId,
        },
    };

    return Ajax.call([request])[0];
};

/**
 * Completes an MTN Africa payment
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @param {string} transactionId The order id coming back from MTN Africa
 * @returns {Promise<{success: string, message: string}>}
 */
export const transactionComplete = (component, paymentArea, itemId, transactionId) => {
    const request = {
        methodname: 'paygw_mtnafrica_transaction_complete',
        args: {
            component,
            paymentarea: paymentArea,
            itemid: itemId,
            transactionid: transactionId,
        },
    };

    return Ajax.call([request])[0];
};
