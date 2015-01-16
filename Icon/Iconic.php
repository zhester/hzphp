<?php

namespace hzphp\Icon;


/**
 *  Iconic Icon Database
 *
 *  Source: http://www.somerandomdude.com/work/open-iconic/
 *  Updated (2014-04-21): https://useiconic.com/open
 */
class Iconic extends Database {


    protected static    $size = 32;


    protected static    $icons = [

        'arrow_down' => [
            'm 32,16 -5.672,-5.664 -6.312,6.312 0,-16.664 -8,0 0,16.664 -6.32,-6.32 L 0,16.016 16,32 z'
        ],

        'arrow_left' => [
            'm 16,32 5.672,-5.672 -6.312,-6.312 16.656,0 0,-8 -16.656,0 6.312,-6.32 L 16,0 0,16 z'
        ],

        'arrow_right' => [
            'm 16,0 -5.668,5.672 6.312,6.312 -16.66,0 0,8 16.66,0 -6.316,6.316 L 16,32 32,16 z'
        ],

        'arrow_up' => [
            'm 0,16 5.672,5.664 6.314,-6.312 0,16.664 8,0 0,-16.664 6.32,6.32 L 32,16 16,0 z'
        ],

        'check' => [
            'M 11.941,17.541082 26.305,3.1780822 32,8.8730822 11.941,28.932082 l 0,0 -11.941,-11.942 5.695,-5.695 z'
        ],

        'check_fill' => [
            'M 16,0 C 7.164,0,0,7.164,0,16 s 7.164,16,16,16 s 16-7.164,16-16 S 24.836,0,16,0 z M 13.52,23.383 L 6.158,16.02 l 2.828-2.828 l 4.533,4.535l9.617-9.617 l 2.828,2.828 L 13.52,23.383 z'
        ],

        'chevron_down' => [
            'M 6,4 l -6,6 16,16, 16,-16 -6,-6 -10,10, -10,-10 z'
        ],

        'chevron_left' => [
            'M 20,0 l -16,16 16,16, 6,-6 -10,-10 10,-10, -6,-6 z'
        ],

        'chevron_right' => [
            'M 10,0 l -6,6 10,10, -10,10 6,6 16,-16, -16,-16 z'
        ],

        'chevron_up' => [
            'M 16,4 l -16,16 6,6, 10,-10 10,10 6,-6, -16,-16 z'
        ],

        'cog' => [
            'm 32,17.969 0,-4 -4.781,-1.992 -0.445,-1.094 1.93,-4.805 L 25.875,3.25 21.113,5.211 19.996,4.75 17.969,0 l -4,0 -1.977,4.734 -1.16,0.469 L 6.078,3.293 3.25,6.121 5.188,10.832 4.703,12.02 0,14.031 l 0,4 4.707,1.961 0.488,1.188 -1.902,4.742 2.828,2.828 4.723,-1.945 1.164,0.461 2.023,4.734 4,0 1.98,-4.758 1.113,-0.461 4.797,1.922 2.828,-2.828 -1.969,-4.773 0.438,-1.094 z M 15.969,22 c -3.312,0 -6,-2.688 -6,-6 0,-3.312 2.688,-6 6,-6 3.312,0 6,2.688 6,6 0,3.312 -2.688,6 -6,6 z'
        ],

        'denied' => [
            'M 16,0 C 7.164,0,0,7.164,0,16 s 7.164,16,16,16 s 16-7.164,16-16 S 24.836,0,16,0 z M 16,4 c 2.59,0,4.973,0.844,6.934,2.242L6.238,22.93C4.84,20.969,4,18.586,4,16 C 4,9.383,9.383,4,16,4 z M 16,28 c -2.59,0-4.973-0.844-6.934-2.242 L 25.762,9.07 C 27.16,11.031,28,13.414,28,16 C 28,22.617,22.617,28,16,28 z'
        ],

        'document' => [
            'M 18,0 4,0 4,32 28,32 28,9.998 z M 8,28 8,4 l 8,0 0,8 8,0 0,16 z'
        ],

        'document_filled' => [
            'M 18,0 4,0 4,32 28,32 28,9.998 z m -2.082,12 0,-8 8,8 z'
        ],

        'download' => [
            'm 4,27.43 24,0 0,4.57 -24,0 z',
            'M 20,18.285 20,0 l -8,0 0,18.285 -4,0 8.012,9.145 L 24,18.285 z'
        ],

        'folder' => [
            'M 32,30 c 0,1.109 -0.895,2 -2,2 H 2 c -1.105,0 -2 -0.891 -2 -2 V 12 h 32 V 30 z', 'M 12,4.002 12,0 0,0 0,8 32,8 32,4.002 z'
        ],

        'info' => [
            'm 10,16 c 1.105,0 2,0.895 2,2 v 8 c 0,1.105 -0.895,2 -2,2 H 8 v 4 h 16 v -4 h -1.992 c -1.102,0 -2,-0.895 -2,-2 L 20,12 H 8 v 4 h 2 z',
            'm 20,4 c 0,2.209139 -1.790861,4 -4,4 -2.209139,0 -4,-1.790861 -4,-4 0,-2.209139 1.790861,-4 4,-4 2.209139,0 4,1.790861 4,4 z'
        ],

        'info_fill' => [
            'M 16,0 C 7.164,0 0,7.164 0,16 0,24.836 7.164,32 16,32 24.836,32 32,24.836 32,16 32,7.164 24.836,0 16,0 z m 0,4.8771186 c 1.535239,0 2.78072,1.2454813 2.78072,2.7807204 0,1.535239 -1.245481,2.78072 -2.78072,2.78072 -1.535239,0 -2.78072,-1.245481 -2.78072,-2.78072 0,-1.5352391 1.245481,-2.7807204 2.78072,-2.7807204 z m -5.561441,8.3421614 8.342161,0 0,9.732521 c 0,0.767918 0.624527,1.39036 1.390361,1.39036 l 1.39036,0 0,2.78072 -11.122882,0 0,-2.78072 1.39036,0 c 0.767919,0 1.390361,-0.622442 1.390361,-1.39036 l 0,-5.561441 C 13.21928,16.622442 12.596838,16 11.828919,16 l -1.39036,0 z'
        ],

        'minus' => [
            'm 32,12 -32,0 0,8 32,0 z'
        ],

        'paperclip' => [
            'm 21.998871,-0.00149249 c -2.057929,0 -4.087832,0.80475445 -5.629277,2.37822959 L 5.235155,13.262943 c -4.28401627,4.284016 -4.28401627,11.230529 0,15.514545 4.2840163,4.284016 11.230529,4.284016 15.514545,0 l 5.004692,-5.004692 -2.754582,-2.754583 -3.627401,3.503285 -1.377291,1.501407 c -2.76259,2.76259 -7.246794,2.76259 -10.0093843,0 -2.7305599,-2.73056 -2.6745074,-7.118673 0,-9.885267 L 19.120172,5.2514321 V 5.1273158 c 1.557461,-1.5814827 4.151893,-1.6015014 5.757398,0 1.553456,1.5254301 1.513419,4.027776 0,5.6292772 l -10.009384,9.885268 c -0.380356,0.380356 -1.121051,0.380356 -1.501407,0 -0.380357,-0.380357 -0.380357,-1.121051 0,-1.501408 l 1.501407,-1.377291 2.37823,-2.502346 -2.754583,-2.754582 -3.503284,3.503284 -0.376353,0.376353 c -1.9418205,1.94182 -1.9418205,5.064748 0,7.006568 1.941821,1.941821 5.064748,1.941821 7.006569,0 l 10.009384,-9.761151 c 3.122927,-3.122928 3.142946,-8.1716607 0,-11.2625584 C 26.066685,0.80726572 24.052797,-0.0095 21.998871,-0.0095 z'
        ],

        'pencil' => [
            'M 24,0 l -4,4 8,8 4,-4 -8,-8 z',
            'M 16,8 l -16,16 v 8 h 8 l 16,-16 -8,-8 z'
        ],

        'plus' => [
            'm 32,12 -12,0 0,-12 -8,0 0,12 -12,0 0,8 12,0 0,12 8,0 0,-12 12,0 z'
        ],

        'question_fill' => [
            'M 16,0 C 9.7,-0.09 3.64,3.96 1.26,9.77 -1.23,15.49 0.06,22.58 4.42,27.04 8.61,31.56 15.53,33.20 21.32,31.10 27.32,29.06 31.74,23.16 31.98,16.82 32.38,10.66 28.81,4.50 23.28,1.75 21.04,0.60 18.52,0 16,0 z m 0,5.34 c 3.04,-0.12 6.37,1.67 7.46,4.68 0.81,2.06 -0.09,4.36 -1.69,5.76 -1.17,1.35 -2.67,2.19 -3.50,3.80 C 17.48,20.45 17.69,21.75 17.69,21.75 c 0,0 -3.03,0 -3.97,0 -0.20,-2 0.19,-4.19 1.79,-5.54 1.14,-1.25 2.83,-2.01 3.67,-3.52 0.83,-1.83 -0.82,-3.90 -2.74,-3.91 -3.32,-0.17 -4.65,2.44 -4.82,3.81 0,0 -2.77,-0.58 -3.61,-0.69 1.03,-4.18 4.28,-6.45 7.66,-6.57 z M 13.72,23.19 c 1.36,0 2.73,0 4.09,0 0,1.36 0,2.73 0,4.09 -1.36,0 -2.73,0 -4.09,0 0,-1.36 0,-2.73 0,-4.09 z'
        ],

        'save' => [
            'M 26.00053,32 32,32 32,0 0,0 0,29.220339 2.8379237,32 6.625,32 z M 6.625,28.685149 l 0,-11.272799 19.37553,0 0,11.272799 z m 2.5820872,-8.974569 0,6.579214 5.0937498,0 0,-6.579214 z'
        ],

        'upload' => [
            'm 4,0 24,0 0,4.572 -24,0 z',
            'M 12,13.715 12,32 l 8,0 0,-18.285 4,0 L 15.988,4.572 8,13.715 z'
        ],

        'x' => [
            'M 30,24.398 21.594,16 30,7.602 24.398,2 16,10.402 7.598,2 2,7.602 10.398,16 2,24.398 7.598,30 16,21.598 24.398,30 z'
        ],

        'x_fill' => [
            'M 16,0 C 7.164,0,0,7.164,0,16 s 7.164,16,16,16 s 16-7.164,16-16 S 24.836,0,16,0 z M 23.914,21.086 l -2.828,2.828 L 16,18.828 l -5.086,5.086 l -2.828-2.828 L 13.172,16 l -5.086-5.086 l 2.828-2.828 L 16,13.172 l 5.086-5.086 l 2.828,2.828 L 18.828,16 L 23.914,21.086 z'
        ]

    ];


}


