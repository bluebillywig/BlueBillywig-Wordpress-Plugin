jQuery(document).ready(function ($) {
    if (!bbPluginData) {
        console.warn("No Blue Billywig plugin data found");
    } else {
        publication = bbPluginData.publication;
        allPlayouts = bbPluginData.allPlayouts;
        ajaxurl = bbPluginData.ajaxurl;
        defaultPlayout = bbPluginData.defaultPlayout || "default";
        defaultAutoplay = bbPluginData.autoplay === "1" || bbPluginData.autoplay === true || bbPluginData.autoplay === 1;
        BB_STRINGS = bbPluginData.strings;
    }

    (function (blocks, editor, i18n, element) {
        var el = element.createElement;
        var __ = i18n.__;
        var InspectorControls = editor.InspectorControls;
        var ToggleControl = wp.components.ToggleControl;

        var blockStyle = {
            backgroundColor: '#ececec',
            color: '#020202',
            padding: '20px',
        };

        blocks.registerBlockType(BB_STRINGS["BLOCK_NAME"], {
            title: __(BB_STRINGS["BLOCK_TITLE"]),
            description: BB_STRINGS["BLOCK_DESCRIPTION"],
            icon: BB_STRINGS["BLOCK_ICON"],
            category: 'embed',
            attributes: {
                clipID: {
                    type: 'number'
                },
                playout: {
                    type: 'string'
                },
                autoplay: {
                    type: 'boolean'
                }
            },
            edit: function (props) {
                var clipID = props.attributes.clipID;
                var playout = props.attributes.playout == null ? defaultPlayout : props.attributes.playout;
                var autoplay = typeof props.attributes.autoplay === 'undefined' ? defaultAutoplay : props.attributes.autoplay;
                window.bbVideoProperties = props;

                var playoutSelectLabel = el('span', {}, BB_STRINGS["BLOCK_SELECT_PLAYOUT_LABEL"]);
                var playoutSelectElement = el('select', {
                    class: 'bbPlayoutSelect',
                    onChange: function (e) {
                        if (props.attributes.clipID && e.target.value !== props.attributes.playout && window.bluebillywig && window.bluebillywig.players && window.bluebillywig.players.length) {
                            for (var i = 0; i < window.bluebillywig.players.length; i++) {
                                var player = window.bluebillywig.players[i];
                                if (player._id === '/p/' + playout + '/c/' + clipID) {
                                    player.destruct();
                                }
                            }
                        }

                        props.setAttributes({
                            playout: e.target.value
                        })
                    }
                }, createOptions(allPlayouts, element));

                var clipSearchLabel = el('span', {}, BB_STRINGS["BLOCK_SEARCH_CLIP_LABEL"]);
                var clipSearchInput = el('input', {
                        placeholder: BB_STRINGS["BLOCK_SEARCH_PLACEHOLDER"],
                        class: 'bbVideoSearch',
                        type: "text",
                        autoFocus: true,
                        onKeyDown: function (e) {
                            if (e.keyCode === 13) {
                                onSubmit(e.target.parentElement);
                            }
                        }
                });
                var clipSearchSubmit = el('input', {
                        value: BB_STRINGS["BLOCK_SEARCH_SUBMIT_LABEL"],
                        type: "submit",
                        onClick: function (e) {
                            onSubmit(e.target.parentElement);
                        }
                    });
                var clipSearchWrapper = el('div', {
                        class: BB_STRINGS["ELEMENT_ID_LIBRARY_WRAPPER"]
                });
                var autoplayToggle = el(ToggleControl, {
                    checked: autoplay,
                    label: BB_STRINGS["BLOCK_AUTOPLAY_TOGGLE_LABEL"],
                    onChange: function (e) {
                        autoplay = e;
                        props.setAttributes({autoplay: e});
                    }
                });

                if (clipID && clipID !== 0) {
                    var content = el('div', {
                        style: blockStyle,
                        class: BB_STRINGS["ELEMENT_ID_VIDEO_WRAPPER"],
                        id: (BB_STRINGS["ELEMENT_ID_CLIP"] + clipID)
                    });

                    insertPlayer(clipID, playout);

                    return [el(
                            InspectorControls,
                            { key: 'inspector' },
                            playoutSelectLabel,
                            playoutSelectElement,
                            clipSearchLabel,
                            clipSearchInput,
                            clipSearchSubmit,
                            clipSearchWrapper,
                            autoplayToggle,
                            ), content];
                } else {
                    return (
                            el(
                                'div',
                                {
                                    key: 'bbBlock',
                                    style: blockStyle,
                                    class: BB_STRINGS["ELEMENT_ID_VIDEO_WRAPPER"]
                                },
                                playoutSelectLabel,
                                playoutSelectElement,
                                clipSearchLabel,
                                clipSearchInput,
                                clipSearchSubmit,
                                clipSearchWrapper,
                                autoplayToggle,
                            )
                    );
                }
            },
            save: function (props) {
                var clipID = props.attributes.clipID;
                var playout = props.attributes.playout || defaultPlayout;
                var autoplay = (typeof props.attributes.autoplay !== 'undefined') ? props.attributes.autoplay : defaultAutoplay;

                if (clipID && clipID !== 0) {
                    var shortcode = '[bbmediaclip clipID="' + clipID + '" playout="' + playout + '"';
                    if (autoplay) {
                        shortcode += ' autoplay="true"';
                    }
                    shortcode += ']';
                    var content = el('p', {},
                        shortcode);
                    return content;
                }
                return el(
                    'p', {
                        style: blockStyle
                    },
                    BB_STRINGS["FEEDBACK_INVALID_ID"]
                );
            },
        });
    }(
        window.wp.blocks,
        window.wp.blockEditor || window.wp.editor,
        window.wp.i18n,
        window.wp.element
    ));


});

function onSubmit(rootElement) {
    var inputs = jQuery(rootElement).find('input[type=text]');
    var query = '';
    if (inputs.length > 0) {
        query = inputs[0].value;
    }

    searchForVideos(rootElement, query);
}

function clearVideos(rootElement) {
    var wrapper = jQuery(rootElement).find('.' + BB_STRINGS["ELEMENT_ID_LIBRARY_WRAPPER"])[0];
    while (wrapper.firstChild) {
        wrapper.removeChild(wrapper.firstChild);
    }
}

function searchForVideos(rootElement, query) {
    var wrapper = jQuery(rootElement).find('.' + BB_STRINGS["ELEMENT_ID_LIBRARY_WRAPPER"]);
    jQuery.ajax({
        url: ajaxurl,
        data: {
            'action': 'search_videos_request',
            'query': query,
            'status': 'published'
        },
        success: function (data) {
            clearVideos(rootElement);

            if (data == '') {
                wrapper.append("<pre class='bb-no-result'>" + BB_STRINGS["FEEDBACK_NO_VIDEOS"] + "</pre>");
            } else {
                wrapper.append(data);
            }
        },
        error: function (errorThrown) {
            clearVideos(rootElement);
            wrapper.append("<pre>" + BB_STRINGS["FEEDBACK_ERROR"] + errorThrown + "</pre>");
        }
    });
}

function selectClip(clipID) {
    if (window.bbVideoProperties) {
        if (window.bluebillywig && window.bluebillywig.players) {
            var destroyId = '/p/' + (window.bbVideoProperties.attributes.playout || defaultPlayout ) + '/c/' + window.bbVideoProperties.attributes.clipID;
            for (var i = 0; i < window.bluebillywig.players.length; i++) {
                var player = window.bluebillywig.players[i];
                if (player._id === '/p/' + (window.bbVideoProperties.attributes.playout || defaultPlayout ) + '/c/' + window.bbVideoProperties.attributes.clipID) {
                    player.destruct();
                }
            }
        }

        window.bbVideoProperties.setAttributes({
            clipID: clipID
        });
    }
}

function insertPlayer(clipID, playout) {
    var videoElementID = 'bb-wr-' + playout + '-' + clipID;
    var videoWrapper = document.getElementById(videoElementID);

    if (!videoWrapper) {
        var player = document.createElement('script');
        player.src = getClipScriptURL(clipID, playout);

        //Give React some time to add the wrapper to the page and insert the player
        setTimeout(function () {
            var wrapper = document.getElementById('bb-video-' + clipID);
            wrapper.append(player);
        }, 10);
    }
}

function createOptions(playouts, element) {
    var defaultPlayoutEnd = playouts.indexOf(',');
    var defaultPlayout = playouts.substring(0, defaultPlayoutEnd);
    defaultPlayout = {
        label: defaultPlayout.split(':')[0],
        value: defaultPlayout.split(':')[1],
        selected: true
    };
    var otherPlayouts = playouts.substring(defaultPlayoutEnd + 1).split(',') || "default";
    var optionElements = [element.createElement("option", defaultPlayout)] || "default";

    for (let i = 0; i < otherPlayouts.length; i++) {
        var currentPlayout = otherPlayouts[i].split(":");
        optionElements.push(element.createElement("option", {
            label: currentPlayout[1],
            value: currentPlayout[0]
        }));
    }
    return element.concatChildren(optionElements);
}

function getClipScriptURL(clipID, playout) {
    return 'https://' + publication + '.bbvms.com/p/' + playout + '/c/' + clipID + '.js?autoPlay=false';
}
