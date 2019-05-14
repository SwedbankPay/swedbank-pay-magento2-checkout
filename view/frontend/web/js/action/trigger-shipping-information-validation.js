define([
    'jquery',
    'ko',
], function ($, _) {
    'use strict';

    return {
        trigger: function(callback){
            callback({success: false, message: 'no function attached'});
        }
    }
});