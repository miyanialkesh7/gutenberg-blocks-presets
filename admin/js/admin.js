/**
 * Admin JavaScript for Gutenberg Blocks Presets
 *
 * @package Gutenberg_Blocks_Presets
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize admin functionality
    GBP_Admin.init();
});

/**
 * Main Admin Object
 */
var GBP_Admin = {
    
    /**
     * Initialize admin functionality
     */
    init: function() {
        this.initCopyToClipboard();
        this.initFormValidation();
        this.initToolTips();
        this.initCharts();
    },

    /**
     * Initialize copy to clipboard functionality
     */
    initCopyToClipboard: function() {
        // Copy shortcode/function code when clicked
        $(document).on('click', '.gbp-widget input[readonly], .gbp-tool-card input[readonly]', function() {
            this.select();
            document.execCommand('copy');
            
            // Show temporary feedback
            var $input = $(this);
            var originalBg = $input.css('background-color');
            $input.css('background-color', '#d4edda');
            
            setTimeout(function() {
                $input.css('background-color', originalBg);
            }, 1000);
        });
    },

    /**
     * Initialize form validation
     */
    initFormValidation: function() {
        // Validate block folders input
        $('#gbp_settings_block_folders').on('blur', function() {
            var value = $(this).val();
            var lines = value.split('\n');
            var hasErrors = false;
            
            $.each(lines, function(index, line) {
                line = line.trim();
                if (line && line.indexOf('..') !== -1) {
                    hasErrors = true;
                    return false;
                }
            });
            
            if (hasErrors) {
                $(this).css('border-color', '#dc3232');
                alert(gbp_admin.strings.invalid_path || 'Invalid path detected. Paths cannot contain ".."');
            } else {
                $(this).css('border-color', '');
            }
        });
    },

    /**
     * Initialize tooltips
     */
    initToolTips: function() {
        // Add tooltip functionality for help icons
        $('.gbp-help-tip').on('mouseenter', function() {
            var tip = $(this).data('tip');
            if (tip) {
                $('<div class="gbp-tooltip"></div>')
                    .text(tip)
                    .appendTo('body')
                    .fadeIn('fast');
            }
        }).on('mouseleave', function() {
            $('.gbp-tooltip').remove();
        }).on('mousemove', function(e) {
            $('.gbp-tooltip').css({
                top: e.pageY + 10,
                left: e.pageX + 10
            });
        });
    },

    /**
     * Initialize charts and data visualization
     */
    initCharts: function() {
        // Enhanced chart rendering if Chart.js is available
        if (typeof Chart !== 'undefined' && $('#gbp-usage-chart').length) {
            this.renderUsageChart();
        }
    },

    /**
     * Render usage chart with Chart.js
     */
    renderUsageChart: function() {
        var ctx = document.getElementById('gbp-usage-chart').getContext('2d');
        
        // Get data from the page (should be localized)
        var chartData = window.gbp_chart_data || [];
        
        if (chartData.length === 0) {
            return;
        }

        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(function(item) { return item.month; }),
                datasets: [{
                    label: 'Usage Count',
                    data: chartData.map(function(item) { return item.total_usage; }),
                    backgroundColor: 'rgba(0, 115, 170, 0.7)',
                    borderColor: 'rgba(0, 115, 170, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Block Usage Over Time'
                    }
                }
            }
        });
    }
};

/**
 * Block preset management functions
 */
var GBP_BlockPresets = {
    
    /**
     * Preview block preset
     */
    preview: function(blockId) {
        if (!blockId) return;
        
        $.ajax({
            url: gbp_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'gbp_preview_block',
                block_id: blockId,
                nonce: gbp_admin.nonce
            },
            beforeSend: function() {
                $('#gbp-preview-container').html('<p>' + (gbp_admin.strings.loading || 'Loading...') + '</p>');
            },
            success: function(response) {
                if (response.success) {
                    $('#gbp-preview-container').html(response.data.content);
                } else {
                    $('#gbp-preview-container').html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function() {
                $('#gbp-preview-container').html('<p class="error">Preview failed to load.</p>');
            }
        });
    },

    /**
     * Duplicate block preset
     */
    duplicate: function(blockId) {
        if (!blockId || !confirm(gbp_admin.strings.confirm_duplicate || 'Duplicate this block preset?')) {
            return;
        }
        
        $.ajax({
            url: gbp_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'gbp_duplicate_block',
                block_id: blockId,
                nonce: gbp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Duplication failed.');
                }
            }
        });
    }
};

/**
 * Utility functions
 */
var GBP_Utils = {
    
    /**
     * Format number with commas
     */
    numberFormat: function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },

    /**
     * Debounce function
     */
    debounce: function(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
};
