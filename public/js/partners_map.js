/**
 * public/js/partners_map.js
 *
 * Renders an interactive world map (Leaflet) on the student homepage,
 * showing AMU or IUT partner institutions as markers with a custom pin
 * icon. Partner data is fetched from index.php?page=api-partners-map&site=..
 * Clicking a marker opens a popup with the institution details. Institutions
 * sharing the same coordinates share one marker, otherwise only the top one
 * could be clicked.
 * A toggle group (.map-toggle-btn) switches between the two datasets
 * without reloading the page.
 *
 * Depends on:
 *   - Leaflet (leaflet.js / leaflet.css), loaded before this script
 *   - Leaflet.markercluster, loaded before this script (groups nearby markers;
 *     the map still works without it, with every marker shown individually)
 *   - #app-config[data-api-partners-map] and [data-icon-localisation]
 *   - #partners-map, an empty <div> to host the map
 *   - .map-toggle-btn[data-site="amu"|"iut"] toggle buttons
 */
(function () {
    'use strict';

    var LABELS = {
        fr: { city: 'Ville', country: 'Pays', continent: 'Continent', offers: 'Ce qu\'elle propose' },
        en: { city: 'City', country: 'Country', continent: 'Continent', offers: 'What it offers' },
    };

    document.addEventListener('DOMContentLoaded', function () {
        var mapContainer = document.getElementById('partners-map');
        if (!mapContainer || typeof L === 'undefined') {
            // No map on this page, or Leaflet failed to load.
            return;
        }

        var config = document.getElementById('app-config');
        var apiUrl = config ? config.getAttribute('data-api-partners-map') : null;
        var iconUrl = config ? config.getAttribute('data-icon-localisation') : 'img/localisation.png';
        var labels = LABELS[config ? config.getAttribute('data-lang') : 'fr'] || LABELS.fr;

        if (!apiUrl) {
            console.error('partners_map.js: missing data-api-partners-map on #app-config');
            return;
        }

        var partnerIcon = L.icon({
            iconUrl: iconUrl,
            iconSize: [26, 26],
            iconAnchor: [13, 26],
            popupAnchor: [0, -26],
        });

        var map = L.map(mapContainer, {
            worldCopyJump: true,
        }).setView([20, 10], 2);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18,
        }).addTo(map);

        var markersLayer = (typeof L.markerClusterGroup === 'function'
                ? L.markerClusterGroup({
                    maxClusterRadius: 45,
                    showCoverageOnHover: false,
                    iconCreateFunction: function (cluster) {
                        return L.divIcon({
                            className: 'partner-cluster',
                            html: '<span>' + cluster.getChildCount() + '</span>',
                            iconSize: [38, 38],
                        });
                    },
                })
                : L.layerGroup()
        ).addTo(map);
        var currentSite = 'amu';
        var abortController = null;

        function createElement(tag, className, text) {
            var element = document.createElement(tag);
            element.className = className;
            // textContent keeps database values from being interpreted as HTML.
            element.textContent = text || '';
            return element;
        }

        function addDetail(list, label, value) {
            if (!value) {
                return;
            }
            var row = createElement('div', 'partner-popup__row');
            row.appendChild(createElement('dt', '', label));
            row.appendChild(createElement('dd', '', value));
            list.appendChild(row);
        }

        function partnerCard(partner) {
            var card = createElement('article', 'partner-popup__card');
            card.appendChild(createElement('h3', 'partner-popup__name', partner.nom));

            var details = createElement('dl', 'partner-popup__details');
            addDetail(details, labels.city, partner.ville);
            addDetail(details, labels.country, partner.pays);
            addDetail(details, labels.continent, partner.continent);
            card.appendChild(details);

            if (partner.offres) {
                card.appendChild(createElement('h4', 'partner-popup__offers-title', labels.offers));
                card.appendChild(createElement('p', 'partner-popup__offers', partner.offres));
            }

            return card;
        }

        function popupContent(partners) {
            var wrapper = createElement('div', 'partner-popup');
            partners.forEach(function (partner) {
                wrapper.appendChild(partnerCard(partner));
            });
            return wrapper;
        }

        function groupByLocation(partners) {
            var groups = new Map();
            partners.forEach(function (partner) {
                var key = partner.lat + ',' + partner.lng;
                if (!groups.has(key)) {
                    groups.set(key, []);
                }
                groups.get(key).push(partner);
            });
            return Array.from(groups.values());
        }

        function loadPartners(site) {
            currentSite = site;
            markersLayer.clearLayers();

            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            mapContainer.setAttribute('aria-busy', 'true');

            fetch(apiUrl + (apiUrl.indexOf('?') === -1 ? '?' : '&') + 'site=' + encodeURIComponent(site), {
                signal: abortController.signal,
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (currentSite !== site) {
                        return; // A newer request has since been issued.
                    }

                    var partners = (data && data.partners) || [];
                    var bounds = [];

                    groupByLocation(partners).forEach(function (group) {
                        var position = [group[0].lat, group[0].lng];
                        var names = group.map(function (partner) {
                            return partner.nom;
                        }).join(', ');
                        var marker = L.marker(position, { icon: partnerIcon, title: names, alt: names });
                        marker.bindPopup(popupContent(group), { minWidth: 240, maxWidth: 320 });
                        markersLayer.addLayer(marker);
                        bounds.push(position);
                    });

                    if (bounds.length > 0) {
                        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 5 });
                    }
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError') {
                        console.error('partners_map.js: failed to load partners', error);
                    }
                })
                .finally(function () {
                    mapContainer.removeAttribute('aria-busy');
                });
        }

        var toggleButtons = document.querySelectorAll('.map-toggle-btn');
        toggleButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (btn.classList.contains('active')) {
                    return;
                }
                toggleButtons.forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');
                loadPartners(btn.getAttribute('data-site'));
            });
        });

        loadPartners(currentSite);
    });
})();