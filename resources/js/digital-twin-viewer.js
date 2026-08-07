import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';

const viewerDisposers = new WeakMap();
const viewerControllers = new WeakMap();
const markerTextureCache = new Map();

function parseJsonScript(root, selector) {
    const script = root.querySelector(selector);

    if (!script) {
        return [];
    }

    try {
        const parsed = JSON.parse(script.textContent || '[]');
        return Array.isArray(parsed) ? parsed : Object.values(parsed);
    } catch (error) {
        console.warn(`Unable to parse digital twin JSON from ${selector}`, error);
        return [];
    }
}

function parseSources(root) {
    return parseJsonScript(root, '[data-twin-sources]');
}

function parseMarkers(root) {
    return parseJsonScript(root, '[data-twin-markers]')
        .filter((marker) => marker && vectorFromPayload(marker.position));
}

function sourceUrl(source) {
    return source.fileUrl || source.externalUrl || source.downloadUrl || '';
}

function createRenderer() {
    const renderer = new THREE.WebGLRenderer({
        antialias: true,
        powerPreference: 'high-performance',
    });

    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2.5));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.12;

    return renderer;
}

function enhanceModelForInspection(model, renderer, source = {}) {
    const anisotropy = Math.max(1, renderer.capabilities.getMaxAnisotropy?.() || 1);
    const isMatterPakModel = source.provider === 'matterport'
        || String(source.originalFormat || '').toLowerCase().includes('matterpak');

    model.traverse((object) => {
        if (!object.isMesh) {
            return;
        }

        object.castShadow = false;
        object.receiveShadow = false;

        if (!object.geometry.attributes.normal) {
            object.geometry.computeVertexNormals();
        }

        const materials = Array.isArray(object.material) ? object.material : [object.material];
        materials.filter(Boolean).forEach((material) => {
            if (isMatterPakModel) {
                material.side = THREE.DoubleSide;
            }

            enhanceTexture(material.map, anisotropy, true);
            enhanceTexture(material.emissiveMap, anisotropy, true);
            enhanceTexture(material.aoMap, anisotropy);
            enhanceTexture(material.bumpMap, anisotropy);
            enhanceTexture(material.displacementMap, anisotropy);
            enhanceTexture(material.metalnessMap, anisotropy);
            enhanceTexture(material.normalMap, anisotropy);
            enhanceTexture(material.roughnessMap, anisotropy);
            enhanceTexture(material.alphaMap, anisotropy);
            material.needsUpdate = true;
        });
    });
}

function applyInspectionEnvironment(scene, renderer) {
    const pmremGenerator = new THREE.PMREMGenerator(renderer);
    const environmentTexture = pmremGenerator.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environment = environmentTexture;

    return () => {
        if (scene.environment === environmentTexture) {
            scene.environment = null;
        }

        environmentTexture.dispose();
        pmremGenerator.dispose();
    };
}

function enhanceTexture(texture, anisotropy, isColorTexture = false) {
    if (!texture) {
        return;
    }

    texture.anisotropy = anisotropy;
    texture.minFilter = THREE.LinearMipmapLinearFilter;
    texture.magFilter = THREE.LinearFilter;

    if (isColorTexture) {
        texture.colorSpace = THREE.SRGBColorSpace;
    }

    texture.needsUpdate = true;
}

function clearStage(stage) {
    const disposer = viewerDisposers.get(stage);

    if (typeof disposer === 'function') {
        disposer();
    }

    viewerDisposers.delete(stage);
    stage.innerHTML = '';
}

function setActiveButton(root, sourceId) {
    root.dataset.currentSource = sourceId || '';

    root.querySelectorAll('[data-twin-source-button]').forEach((button) => {
        button.classList.toggle('is-active', button.dataset.twinSourceButton === sourceId);
    });
}

function updateSourceActions(root, source) {
    const title = root.querySelector('[data-twin-action-title]');
    const meta = root.querySelector('[data-twin-action-meta]');
    const view = root.querySelector('[data-twin-action-view]');
    const addFinding = root.querySelector('[data-twin-action-add-finding]');
    const openSource = root.querySelector('[data-twin-action-open-source]');
    const convertForm = root.querySelector('[data-twin-action-convert-form]');
    const convertButton = root.querySelector('[data-twin-action-convert]');
    const convertIcon = root.querySelector('[data-twin-action-convert-icon]');
    const convertLabel = root.querySelector('[data-twin-action-convert-label]');
    const job = root.querySelector('[data-twin-action-job]');

    if (title) {
        title.textContent = source.title || 'Capture source';
    }

    if (meta) {
        meta.textContent = source.actionMeta || [source.providerLabel, source.sourceTypeLabel].filter(Boolean).join(' / ');
    }

    if (view) {
        view.href = source.viewUrl || '#digitalTwinViewer';
    }

    if (addFinding) {
        const canAddFinding = Boolean(source.canAddFinding);
        addFinding.classList.toggle('d-none', !canAddFinding);
        addFinding.dataset.captureSessionId = source.addFindingCaptureSessionId || source.captureSessionId || '';
        addFinding.dataset.spatialModelId = source.addFindingSpatialModelId || source.spatialModelId || '';
        addFinding.dataset.sourceProvider = source.addFindingSourceProvider || source.provider || 'manual';
        addFinding.dataset.sourceReference = source.addFindingSourceReference || source.title || '';
    }

    if (openSource) {
        const openSourceUrl = source.openSourceUrl || source.downloadUrl || source.externalUrl || source.fileUrl || '';
        openSource.classList.toggle('d-none', !openSourceUrl);
        openSource.href = openSourceUrl || '#';
    }

    if (convertForm) {
        const convertUrl = source.convertUrl || '';
        convertForm.classList.toggle('d-none', !convertUrl);
        convertForm.action = convertUrl || '#';
    }

    if (convertButton) {
        convertButton.disabled = Boolean(source.convertDisabled || !source.convertUrl);
        convertButton.title = source.convertTitle || 'Start MatterPak GLB conversion';
    }

    if (convertIcon) {
        convertIcon.className = `mdi ${source.convertIcon || 'mdi-cube-send'} me-1`;
    }

    if (convertLabel) {
        convertLabel.textContent = source.convertLabel || 'Convert to GLB';
    }

    if (job) {
        job.textContent = source.jobLabel || '';
        job.classList.toggle('d-none', !source.jobLabel);
    }
}

function setActiveMarker(root, markerId) {
    const activeMarkerId = markerId ? String(markerId) : '';

    root.querySelectorAll('[data-twin-marker-card]').forEach((card) => {
        card.classList.toggle('is-active', activeMarkerId !== '' && card.dataset.markerId === activeMarkerId);
    });

    const activeCard = activeMarkerId
        ? root.querySelector(`[data-twin-marker-card][data-marker-id="${cssEscape(activeMarkerId)}"]`)
        : null;

    if (activeCard) {
        activeCard.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
}

function applyMarkerFilter(root) {
    const filter = currentMarkerFilter(root);

    root.querySelectorAll('[data-twin-marker-filter]').forEach((button) => {
        button.classList.toggle('is-active', button.dataset.twinMarkerFilter === filter);
    });

    root.querySelectorAll('[data-twin-marker-card]').forEach((card) => {
        card.classList.toggle('is-hidden', !cardMatchesFilter(card, filter));
    });

    const controller = viewerControllers.get(root);
    if (controller && typeof controller.setFilter === 'function') {
        controller.setFilter(filter);
    }
}

function renderCard(stage, source, title, body, actionLabel = 'Open Source File') {
    const url = sourceUrl(source);
    stage.innerHTML = `
        <div class="twin-viewer-card">
            <div>
                <h3>${escapeHtml(title)}</h3>
                <p>${escapeHtml(body)}</p>
                ${url ? `<a class="btn btn-outline-primary btn-sm mt-3" href="${escapeAttribute(url)}" target="_blank" rel="noopener noreferrer">
                    <i class="mdi mdi-open-in-new me-1"></i>${escapeHtml(actionLabel)}
                </a>` : ''}
            </div>
        </div>
    `;
}

function renderHostedTour(stage, source) {
    const url = source.externalUrl || source.downloadUrl;

    if (!url) {
        renderCard(stage, source, source.title, 'This hosted tour does not have a URL yet.');
        return;
    }

    stage.innerHTML = `
        <div class="twin-frame-wrap">
            <iframe
                class="twin-frame"
                src="${escapeAttribute(url)}"
                title="${escapeAttribute(source.title || 'Hosted digital twin tour')}"
                allow="fullscreen; xr-spatial-tracking"
                allowfullscreen>
            </iframe>
        </div>
    `;
}

function renderImage(stage, source) {
    const url = source.fileUrl || source.externalUrl;

    if (!url) {
        renderCard(stage, source, source.title, 'This image source has no viewable file yet.');
        return;
    }

    stage.innerHTML = `
        <a href="${escapeAttribute(url)}" target="_blank" rel="noopener noreferrer">
            <img class="twin-preview-image" src="${escapeAttribute(url)}" alt="${escapeAttribute(source.title || 'Digital twin evidence image')}">
        </a>
    `;
}

function renderMediaGallery(stage, source) {
    const items = Array.isArray(source.mediaItems) ? source.mediaItems : [];

    if (items.length === 0) {
        renderCard(stage, source, source.title, 'This gallery does not have viewable media files yet.');
        return;
    }

    stage.innerHTML = `
        <div class="twin-media-gallery">
            <div class="twin-media-gallery-header">
                <h3>${escapeHtml(source.title || 'Media gallery')}</h3>
                <span>${items.length} file${items.length === 1 ? '' : 's'}</span>
            </div>
            <div class="twin-media-gallery-grid">
                ${items.map((item) => renderMediaGalleryItem(item)).join('')}
            </div>
        </div>
    `;
}

function renderMediaGalleryItem(item) {
    const url = item.fileUrl || item.externalUrl || item.downloadUrl || '';
    const extension = String(item.extension || '').toLowerCase();
    const isImage = item.viewerType === 'image' || ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension);
    const title = item.title || 'MatterPak media';

    return `
        <a class="twin-media-gallery-item" href="${escapeAttribute(url || '#')}" target="_blank" rel="noopener noreferrer">
            <div class="twin-media-gallery-thumb">
                ${isImage && url
                    ? `<img src="${escapeAttribute(url)}" alt="${escapeAttribute(title)}" loading="lazy">`
                    : '<i class="mdi mdi-file-pdf-box" aria-hidden="true"></i>'}
            </div>
            <div class="twin-media-gallery-caption">${escapeHtml(title)}</div>
        </a>
    `;
}

function renderPdf(stage, source) {
    const url = source.fileUrl || source.externalUrl;

    if (!url) {
        renderCard(stage, source, source.title, 'This document source has no PDF file yet.');
        return;
    }

    stage.innerHTML = `
        <div class="twin-frame-wrap">
            <iframe
                class="twin-frame"
                src="${escapeAttribute(url)}"
                title="${escapeAttribute(source.title || 'Digital twin evidence document')}">
            </iframe>
        </div>
    `;
}

function renderPotree(stage, source) {
    const url = source.fileUrl || source.externalUrl || source.downloadUrl;

    if (!url) {
        renderCard(stage, source, source.title, 'This hosted point-cloud layer does not have a viewer URL yet.');
        return;
    }

    stage.innerHTML = `
        <div class="twin-frame-wrap">
            <iframe
                class="twin-frame"
                src="${escapeAttribute(url)}"
                title="${escapeAttribute(source.title || 'Hosted point cloud viewer')}">
            </iframe>
        </div>
    `;
}

function renderPointCloudPreview(stage, source) {
    const url = source.fileUrl || source.externalUrl || source.downloadUrl;

    if (!url) {
        renderCard(stage, source, source.title, 'This MatterPak point-cloud preview does not have a viewable file yet.');
        return;
    }

    const container = document.createElement('div');
    container.className = 'twin-point-cloud-stage';
    container.tabIndex = 0;
    container.setAttribute('role', 'application');
    container.setAttribute('aria-label', 'MatterPak point cloud preview');
    stage.appendChild(container);

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf5f7fb);

    const camera = new THREE.PerspectiveCamera(55, 1, 0.01, 10000);
    camera.position.set(3, 2.2, 4);

    const renderer = createRenderer();
    container.appendChild(renderer.domElement);
    const disposeInspectionEnvironment = applyInspectionEnvironment(scene, renderer);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;

    const grid = new THREE.GridHelper(10, 20, 0x4b5563, 0x1f2937);
    grid.position.y = -0.01;
    scene.add(grid);

    const status = document.createElement('div');
    status.className = 'twin-point-cloud-status';
    status.textContent = 'Loading MatterPak point cloud preview...';
    container.appendChild(status);

    let frameId = null;
    let disposed = false;
    let defaultCameraPosition = camera.position.clone();
    let defaultCameraTarget = controls.target.clone();
    let removeViewControls = () => {};
    const resizeObserver = new ResizeObserver(resize);

    fetch(url, { credentials: 'same-origin' })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Point cloud preview returned HTTP ${response.status}`);
            }

            return response.json();
        })
        .then((payload) => {
            if (disposed) {
                return;
            }

            const cloud = buildPointCloud(payload);

            if (!cloud) {
                throw new Error('Point cloud preview contains no valid points.');
            }

            scene.add(cloud);
            const frame = frameObject(cloud, camera, controls);
            defaultCameraPosition = camera.position.clone();
            defaultCameraTarget = controls.target.clone();
            const sampled = payload.point_count || cloud.geometry.attributes.position.count;
            const total = payload.source_point_count || sampled;
            status.textContent = `${formatCount(sampled)} sampled points from ${formatCount(total)} MatterPak XYZ points`;
            cloud.material.size = Math.max((frame.maxDimension || 1) * 0.0024, 0.018);
        })
        .catch((error) => {
            console.error('Unable to load MatterPak point cloud preview', error);
            cleanup();
            clearStage(stage);
            renderCard(stage, source, 'Point cloud preview could not be loaded', 'The generated preview file is stored, but the browser could not read it.');
        });

    function resize() {
        const width = Math.max(container.clientWidth, 320);
        const height = Math.max(container.clientHeight, 360);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
    }

    function animate() {
        if (disposed) {
            return;
        }

        controls.update();
        renderer.render(scene, camera);
        frameId = window.requestAnimationFrame(animate);
    }

    function cleanup() {
        disposed = true;
        resizeObserver.disconnect();
        removeViewControls();
        container.removeEventListener('keydown', handleKeyDown);
        container.removeEventListener('pointerdown', focusViewer);
        if (frameId) {
            window.cancelAnimationFrame(frameId);
        }
        controls.dispose();
        disposeInspectionEnvironment();
        renderer.dispose();
        disposeScene(scene);
    }

    resizeObserver.observe(container);
    resize();
    removeViewControls = addViewControls(container, {
        left: () => orbitCamera(camera, controls, -Math.PI / 14, 0),
        right: () => orbitCamera(camera, controls, Math.PI / 14, 0),
        up: () => orbitCamera(camera, controls, 0, -Math.PI / 18),
        down: () => orbitCamera(camera, controls, 0, Math.PI / 18),
        zoomIn: () => zoomCamera(camera, controls, 0.82),
        zoomOut: () => zoomCamera(camera, controls, 1.22),
        reset: resetView,
        fullscreen: () => toggleFullscreen(container),
    });
    container.addEventListener('keydown', handleKeyDown);
    container.addEventListener('pointerdown', focusViewer);
    animate();

    viewerDisposers.set(stage, cleanup);

    function focusViewer() {
        container.focus({ preventScroll: true });
    }

    function resetView() {
        camera.position.copy(defaultCameraPosition);
        controls.target.copy(defaultCameraTarget);
        camera.lookAt(controls.target);
        controls.update();
    }

    function handleKeyDown(event) {
        if (event.altKey || event.ctrlKey || event.metaKey) {
            return;
        }

        const actions = {
            ArrowLeft: () => orbitCamera(camera, controls, -Math.PI / 14, 0),
            ArrowRight: () => orbitCamera(camera, controls, Math.PI / 14, 0),
            ArrowUp: () => orbitCamera(camera, controls, 0, -Math.PI / 18),
            ArrowDown: () => orbitCamera(camera, controls, 0, Math.PI / 18),
            '+': () => zoomCamera(camera, controls, 0.82),
            '=': () => zoomCamera(camera, controls, 0.82),
            '-': () => zoomCamera(camera, controls, 1.22),
            _: () => zoomCamera(camera, controls, 1.22),
            r: resetView,
            R: resetView,
            f: () => toggleFullscreen(container),
            F: () => toggleFullscreen(container),
        };
        const action = actions[event.key];

        if (!action) {
            return;
        }

        event.preventDefault();
        action();
    }
}

function buildPointCloud(payload) {
    const points = Array.isArray(payload?.points) ? payload.points : [];

    if (points.length === 0) {
        return null;
    }

    const positions = new Float32Array(points.length * 3);
    const colors = new Float32Array(points.length * 3);
    let validPoints = 0;

    points.forEach((point) => {
        const x = Number(point?.[0]);
        const y = Number(point?.[1]);
        const z = Number(point?.[2]);

        if (![x, y, z].every(Number.isFinite)) {
            return;
        }

        const colorOffset = validPoints * 3;
        positions[colorOffset] = x;
        positions[colorOffset + 1] = y;
        positions[colorOffset + 2] = z;
        colors[colorOffset] = normalizeColor(point?.[3]);
        colors[colorOffset + 1] = normalizeColor(point?.[4]);
        colors[colorOffset + 2] = normalizeColor(point?.[5]);
        validPoints += 1;
    });

    if (validPoints === 0) {
        return null;
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions.slice(0, validPoints * 3), 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(colors.slice(0, validPoints * 3), 3));
    geometry.computeBoundingBox();

    const material = new THREE.PointsMaterial({
        size: 0.035,
        vertexColors: true,
        sizeAttenuation: true,
    });

    return new THREE.Points(geometry, material);
}

function normalizeColor(value) {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return 1;
    }

    return THREE.MathUtils.clamp(number / 255, 0, 1);
}

function formatCount(value) {
    return Number(value || 0).toLocaleString();
}

function renderThreeModel(stage, source, root, markers = [], options = {}) {
    const url = source.fileUrl || source.externalUrl;

    if (!url) {
        renderCard(stage, source, source.title, 'This 3D model source has no GLB or glTF file yet.');
        return;
    }

    const container = document.createElement('div');
    container.className = 'twin-three-stage';
    container.tabIndex = 0;
    container.setAttribute('role', 'application');
    container.setAttribute('aria-label', '3D digital twin viewer');
    stage.appendChild(container);

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf5f7fb);

    const camera = new THREE.PerspectiveCamera(55, 1, 0.01, 10000);
    camera.position.set(3, 2.2, 4);

    const renderer = createRenderer();
    container.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;

    const raycaster = new THREE.Raycaster();
    const pointer = new THREE.Vector2();
    const pickableObjects = [];
    const markerPickableObjects = [];
    const markerMeshesById = new Map();
    let pointerDown = null;
    let markerPin = null;
    let markerFrameDistance = 2;
    let cameraTransition = null;
    let markerFilter = currentMarkerFilter(root);
    let defaultCameraPosition = camera.position.clone();
    let defaultCameraTarget = controls.target.clone();
    let removeViewControls = () => {};
    let navigationMode = 'orbit';

    const ambient = new THREE.HemisphereLight(0xffffff, 0xb6c2d2, 2.4);
    scene.add(ambient);

    const keyLight = new THREE.DirectionalLight(0xffffff, 2.6);
    keyLight.position.set(4, 6, 5);
    scene.add(keyLight);

    const fillLight = new THREE.DirectionalLight(0xeaf4ff, 1.25);
    fillLight.position.set(-5, 3, -4);
    scene.add(fillLight);

    let frameId = null;
    let disposed = false;
    const resizeObserver = new ResizeObserver(resize);

    const controller = {
        focusMarker,
        setFilter(nextFilter) {
            markerFilter = nextFilter || 'all';
            applyPinFilter();
        },
    };

    viewerControllers.set(root, controller);

    const loader = new GLTFLoader();
    loader.load(
        url,
        (gltf) => {
            const model = gltf.scene;
            enhanceModelForInspection(model, renderer, source);
            scene.add(model);
            model.traverse((object) => {
                if (object.isMesh) {
                    pickableObjects.push(object);
                }
            });
            const frame = frameObject(model, camera, controls);
            markerFrameDistance = frame.distance || markerFrameDistance;
            defaultCameraPosition = camera.position.clone();
            defaultCameraTarget = controls.target.clone();
            addSavedMarkerPins(scene, source, markers, markerPickableObjects, markerMeshesById, frame.maxDimension || 1);
            applyPinFilter();

            if (options.focusMarkerId) {
                window.requestAnimationFrame(() => focusMarker(options.focusMarkerId));
            }
        },
        undefined,
        (error) => {
            console.error('Unable to load GLB/glTF source', error);
            cleanup();
            clearStage(stage);
            renderCard(stage, source, '3D model could not be loaded', 'The file is stored, but the browser viewer could not read it. Confirm it is a valid GLB or glTF file.');
        }
    );

    function resize() {
        const width = Math.max(container.clientWidth, 320);
        const height = Math.max(container.clientHeight, 360);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
    }

    function animate() {
        if (disposed) {
            return;
        }

        updateCameraTransition();
        controls.update();
        renderer.render(scene, camera);
        frameId = window.requestAnimationFrame(animate);
    }

    function cleanup() {
        disposed = true;
        resizeObserver.disconnect();
        removeViewControls();
        container.removeEventListener('keydown', handleKeyDown);
        container.removeEventListener('pointerdown', focusViewer);
        renderer.domElement.removeEventListener('pointerdown', handlePointerDown);
        renderer.domElement.removeEventListener('pointerup', handlePointerUp);
        if (viewerControllers.get(root) === controller) {
            viewerControllers.delete(root);
        }
        if (frameId) {
            window.cancelAnimationFrame(frameId);
        }
        controls.dispose();
        renderer.dispose();
        disposeScene(scene);
    }

    resizeObserver.observe(container);
    resize();
    addPlacementHint(container);
    removeViewControls = addViewControls(container, {
        left: turnLeft,
        right: turnRight,
        up: tiltUp,
        down: tiltDown,
        zoomIn,
        zoomOut,
        walk: toggleWalkMode,
        reset: () => startCameraTransition(defaultCameraPosition, defaultCameraTarget),
        fullscreen: () => toggleFullscreen(container),
    });
    container.addEventListener('keydown', handleKeyDown);
    container.addEventListener('pointerdown', focusViewer);
    renderer.domElement.addEventListener('pointerdown', handlePointerDown);
    renderer.domElement.addEventListener('pointerup', handlePointerUp);
    animate();

    viewerDisposers.set(stage, cleanup);

    function handlePointerDown(event) {
        focusViewer();
        pointerDown = {
            x: event.clientX,
            y: event.clientY,
            time: Date.now(),
        };
    }

    function handlePointerUp(event) {
        if (!pointerDown || event.button !== 0 || (pickableObjects.length === 0 && markerPickableObjects.length === 0)) {
            pointerDown = null;
            return;
        }

        const moved = Math.hypot(event.clientX - pointerDown.x, event.clientY - pointerDown.y);
        const elapsed = Date.now() - pointerDown.time;
        pointerDown = null;

        if (moved > 5 || elapsed > 600) {
            return;
        }

        const rect = renderer.domElement.getBoundingClientRect();
        pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        pointer.y = -(((event.clientY - rect.top) / rect.height) * 2 - 1);
        raycaster.setFromCamera(pointer, camera);

        const [markerHit] = raycaster.intersectObjects(markerPickableObjects, true);

        if (markerHit?.object?.userData?.markerId) {
            focusMarker(markerHit.object.userData.markerId);
            return;
        }

        const [hit] = raycaster.intersectObjects(pickableObjects, true);

        if (!hit) {
            return;
        }

        const worldNormal = hit.face?.normal
            ? hit.face.normal.clone().transformDirection(hit.object.matrixWorld).normalize()
            : new THREE.Vector3(0, 1, 0);

        fillMarkerForm(source, hit.point, worldNormal, camera, controls, hit.object);
        markerPin = placeMarkerPin(container, markerPin, event.clientX - rect.left, event.clientY - rect.top);
        updatePlacementHint(container, hit.point);
    }

    function focusMarker(markerId) {
        const entry = markerMeshesById.get(String(markerId));

        if (!entry) {
            return false;
        }

        setActiveMarker(root, markerId);
        showMarkerTooltip(container, entry.marker);

        const markerPosition = entry.object.position.clone();
        const savedCameraPosition = vectorFromPayload(entry.marker.cameraPosition);
        const savedCameraTarget = vectorFromPayload(entry.marker.cameraTarget);
        const markerNormal = vectorFromPayload(entry.marker.normal) || new THREE.Vector3(0, 1, 0);
        const target = savedCameraTarget || markerPosition;
        const fallbackOffset = markerNormal
            .clone()
            .normalize()
            .multiplyScalar(Math.max(markerFrameDistance * 0.32, 1.2))
            .add(new THREE.Vector3(markerFrameDistance * 0.16, markerFrameDistance * 0.12, markerFrameDistance * 0.16));
        const destination = savedCameraPosition || markerPosition.clone().add(fallbackOffset);

        startCameraTransition(destination, target);
        highlightMarkerPin(markerMeshesById, markerId);

        return true;
    }

    function startCameraTransition(destination, target) {
        cameraTransition = {
            fromPosition: camera.position.clone(),
            fromTarget: controls.target.clone(),
            toPosition: destination.clone(),
            toTarget: target.clone(),
            startedAt: performance.now(),
            duration: 700,
        };
    }

    function updateCameraTransition() {
        if (!cameraTransition) {
            return;
        }

        const progress = Math.min((performance.now() - cameraTransition.startedAt) / cameraTransition.duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);

        camera.position.lerpVectors(cameraTransition.fromPosition, cameraTransition.toPosition, eased);
        controls.target.lerpVectors(cameraTransition.fromTarget, cameraTransition.toTarget, eased);

        if (progress >= 1) {
            cameraTransition = null;
        }
    }

    function applyPinFilter() {
        markerMeshesById.forEach((entry) => {
            entry.object.visible = markerMatchesFilter(entry.marker, markerFilter);
        });
    }

    function focusViewer() {
        container.focus({ preventScroll: true });
    }

    function handleKeyDown(event) {
        if (event.altKey || event.ctrlKey || event.metaKey) {
            return;
        }

        const actions = {
            ArrowLeft: turnLeft,
            ArrowRight: turnRight,
            ArrowUp: tiltUp,
            ArrowDown: tiltDown,
            w: walkForward,
            W: walkForward,
            s: walkBack,
            S: walkBack,
            a: walkLeft,
            A: walkLeft,
            d: walkRight,
            D: walkRight,
            '+': zoomIn,
            '=': zoomIn,
            '-': zoomOut,
            _: zoomOut,
            r: resetView,
            R: resetView,
            f: fullscreen,
            F: fullscreen,
        };
        const action = actions[event.key];

        if (!action) {
            return;
        }

        event.preventDefault();
        action();
    }

    function turnLeft() {
        if (navigationMode === 'walk') {
            turnWalkCamera(-Math.PI / 16);
            return;
        }

        orbitCamera(camera, controls, -Math.PI / 14, 0);
    }

    function turnRight() {
        if (navigationMode === 'walk') {
            turnWalkCamera(Math.PI / 16);
            return;
        }

        orbitCamera(camera, controls, Math.PI / 14, 0);
    }

    function tiltUp() {
        if (navigationMode === 'walk') {
            moveWalkCamera(1);
            return;
        }

        orbitCamera(camera, controls, 0, -Math.PI / 18);
    }

    function tiltDown() {
        if (navigationMode === 'walk') {
            moveWalkCamera(-1);
            return;
        }

        orbitCamera(camera, controls, 0, Math.PI / 18);
    }

    function zoomIn() {
        if (navigationMode === 'walk') {
            moveWalkCamera(1);
            return;
        }

        zoomCamera(camera, controls, 0.82);
    }

    function zoomOut() {
        if (navigationMode === 'walk') {
            moveWalkCamera(-1);
            return;
        }

        zoomCamera(camera, controls, 1.22);
    }

    function resetView() {
        setNavigationMode('orbit');
        startCameraTransition(defaultCameraPosition, defaultCameraTarget);
    }

    function fullscreen() {
        toggleFullscreen(container);
    }

    function toggleWalkMode() {
        setNavigationMode(navigationMode === 'walk' ? 'orbit' : 'walk');
    }

    function setNavigationMode(mode) {
        navigationMode = mode === 'walk' ? 'walk' : 'orbit';
        container.classList.toggle('is-walk-mode', navigationMode === 'walk');
        controls.enabled = navigationMode === 'orbit';

        if (navigationMode === 'walk') {
            const direction = controls.target.clone().sub(camera.position);

            if (direction.lengthSq() < 0.0001) {
                camera.getWorldDirection(direction);
            }

            direction.normalize();
            controls.target.copy(camera.position).add(direction.multiplyScalar(Math.max(markerFrameDistance * 0.12, 2)));
            camera.lookAt(controls.target);
        } else {
            controls.update();
        }

        const walkButton = container.querySelector('[data-view-control="walk"]');
        walkButton?.classList.toggle('is-active', navigationMode === 'walk');
        walkButton?.setAttribute('aria-pressed', navigationMode === 'walk' ? 'true' : 'false');
    }

    function walkForward() {
        moveWalkCamera(1);
    }

    function walkBack() {
        moveWalkCamera(-1);
    }

    function walkLeft() {
        strafeWalkCamera(-1);
    }

    function walkRight() {
        strafeWalkCamera(1);
    }

    function moveWalkCamera(directionMultiplier) {
        if (navigationMode !== 'walk') {
            setNavigationMode('walk');
        }

        const direction = new THREE.Vector3();
        camera.getWorldDirection(direction);
        direction.y = 0;

        if (direction.lengthSq() < 0.0001) {
            return;
        }

        direction.normalize();
        translateWalkCamera(direction.multiplyScalar(walkStep() * directionMultiplier));
    }

    function strafeWalkCamera(directionMultiplier) {
        if (navigationMode !== 'walk') {
            setNavigationMode('walk');
        }

        const direction = new THREE.Vector3();
        camera.getWorldDirection(direction);
        direction.y = 0;

        if (direction.lengthSq() < 0.0001) {
            return;
        }

        direction.normalize();
        const strafe = new THREE.Vector3().crossVectors(direction, new THREE.Vector3(0, 1, 0)).normalize();
        translateWalkCamera(strafe.multiplyScalar(walkStep() * directionMultiplier));
    }

    function translateWalkCamera(delta) {
        camera.position.add(delta);
        controls.target.add(delta);
        camera.lookAt(controls.target);
    }

    function turnWalkCamera(angle) {
        if (navigationMode !== 'walk') {
            setNavigationMode('walk');
        }

        const lookOffset = controls.target.clone().sub(camera.position);

        if (lookOffset.lengthSq() < 0.0001) {
            return;
        }

        lookOffset.applyAxisAngle(new THREE.Vector3(0, 1, 0), angle);
        controls.target.copy(camera.position).add(lookOffset);
        camera.lookAt(controls.target);
    }

    function walkStep() {
        return THREE.MathUtils.clamp(markerFrameDistance * 0.045, 0.28, 1.6);
    }
}

function renderPanorama(stage, source) {
    const url = source.fileUrl || source.externalUrl;

    if (!url) {
        renderCard(stage, source, source.title, 'This panorama source has no image file yet.');
        return;
    }

    const container = document.createElement('div');
    container.className = 'twin-panorama-stage';
    stage.appendChild(container);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(70, 1, 0.1, 1000);
    camera.position.set(0, 0, 0.1);

    const renderer = createRenderer();
    container.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableZoom = true;
    controls.enablePan = false;
    controls.enableDamping = true;
    controls.rotateSpeed = -0.3;

    const geometry = new THREE.SphereGeometry(500, 60, 40);
    geometry.scale(-1, 1, 1);

    const textureLoader = new THREE.TextureLoader();
    let material = null;
    let mesh = null;
    let frameId = null;
    let disposed = false;
    const resizeObserver = new ResizeObserver(resize);

    textureLoader.load(
        url,
        (texture) => {
            texture.colorSpace = THREE.SRGBColorSpace;
            material = new THREE.MeshBasicMaterial({ map: texture });
            mesh = new THREE.Mesh(geometry, material);
            scene.add(mesh);
        },
        undefined,
        (error) => {
            console.error('Unable to load panorama source', error);
            cleanup();
            clearStage(stage);
            renderCard(stage, source, 'Panorama could not be loaded', 'The file is stored, but the browser viewer could not read the panorama image.');
        }
    );

    function resize() {
        const width = Math.max(container.clientWidth, 320);
        const height = Math.max(container.clientHeight, 360);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
    }

    function animate() {
        if (disposed) {
            return;
        }

        controls.update();
        renderer.render(scene, camera);
        frameId = window.requestAnimationFrame(animate);
    }

    function cleanup() {
        disposed = true;
        resizeObserver.disconnect();
        if (frameId) {
            window.cancelAnimationFrame(frameId);
        }
        controls.dispose();
        geometry.dispose();
        material?.map?.dispose();
        material?.dispose();
        renderer.dispose();
    }

    resizeObserver.observe(container);
    resize();
    animate();

    viewerDisposers.set(stage, cleanup);
}

function frameObject(object, camera, controls) {
    const box = new THREE.Box3().setFromObject(object);
    const size = box.getSize(new THREE.Vector3());
    const center = box.getCenter(new THREE.Vector3());
    const maxDimension = Math.max(size.x, size.y, size.z) || 1;
    const verticalFitDistance = maxDimension / (2 * Math.tan(THREE.MathUtils.degToRad(camera.fov) / 2));
    const distance = verticalFitDistance * 1.28;

    object.position.sub(center);
    camera.position.set(distance, distance * 0.7, distance);
    camera.near = Math.max(distance / 100, 0.01);
    camera.far = distance * 100;
    camera.updateProjectionMatrix();
    controls.target.set(0, 0, 0);
    controls.update();

    return {
        center,
        size,
        maxDimension,
        distance,
    };
}

function addViewControls(container, actions) {
    const controls = document.createElement('div');
    controls.className = 'twin-view-controls';
    controls.innerHTML = `
        <div class="twin-view-control-pad">
            <button type="button" class="twin-view-control-button is-pad-up" data-view-control="up" aria-label="Tilt up" title="Tilt up">
                <i class="mdi mdi-arrow-up"></i>
            </button>
            <button type="button" class="twin-view-control-button is-pad-left" data-view-control="left" aria-label="Turn left" title="Turn left">
                <i class="mdi mdi-arrow-left"></i>
            </button>
            <button type="button" class="twin-view-control-button is-pad-reset" data-view-control="reset" aria-label="Reset view" title="Reset view">
                <i class="mdi mdi-crosshairs-gps"></i>
            </button>
            <button type="button" class="twin-view-control-button is-pad-right" data-view-control="right" aria-label="Turn right" title="Turn right">
                <i class="mdi mdi-arrow-right"></i>
            </button>
            <button type="button" class="twin-view-control-button is-pad-down" data-view-control="down" aria-label="Tilt down" title="Tilt down">
                <i class="mdi mdi-arrow-down"></i>
            </button>
        </div>
        <div class="twin-view-control-row">
            ${actions.walk ? `<button type="button" class="twin-view-control-button" data-view-control="walk" aria-label="Toggle walk mode" aria-pressed="false" title="Toggle walk mode">
                <i class="mdi mdi-walk"></i>
            </button>` : ''}
            <button type="button" class="twin-view-control-button" data-view-control="zoomIn" aria-label="Zoom in" title="Zoom in">
                <i class="mdi mdi-magnify-plus-outline"></i>
            </button>
            <button type="button" class="twin-view-control-button" data-view-control="zoomOut" aria-label="Zoom out" title="Zoom out">
                <i class="mdi mdi-magnify-minus-outline"></i>
            </button>
            <button type="button" class="twin-view-control-button" data-view-control="fullscreen" aria-label="Fullscreen" title="Fullscreen">
                <i class="mdi mdi-fullscreen"></i>
            </button>
        </div>
    `;

    const handleClick = (event) => {
        const button = event.target.closest('[data-view-control]');

        if (!button) {
            return;
        }

        event.preventDefault();
        actions[button.dataset.viewControl]?.();
    };

    controls.addEventListener('click', handleClick);
    container.appendChild(controls);

    return () => {
        controls.removeEventListener('click', handleClick);
        controls.remove();
    };
}

function orbitCamera(camera, controls, deltaTheta, deltaPhi) {
    const offset = camera.position.clone().sub(controls.target);
    const spherical = new THREE.Spherical().setFromVector3(offset);

    spherical.theta += deltaTheta;
    spherical.phi = THREE.MathUtils.clamp(spherical.phi + deltaPhi, 0.08, Math.PI - 0.08);
    offset.setFromSpherical(spherical);
    camera.position.copy(controls.target).add(offset);
    camera.lookAt(controls.target);
    controls.update();
}

function zoomCamera(camera, controls, factor) {
    const offset = camera.position.clone().sub(controls.target);
    const nextDistance = THREE.MathUtils.clamp(offset.length() * factor, 0.08, 100000);

    offset.setLength(nextDistance);
    camera.position.copy(controls.target).add(offset);
    camera.lookAt(controls.target);
    controls.update();
}

function toggleFullscreen(element) {
    if (document.fullscreenElement) {
        document.exitFullscreen?.();
        return;
    }

    element.requestFullscreen?.();
}

function addSavedMarkerPins(scene, source, markers, markerPickableObjects, markerMeshesById, modelScale) {
    const sourceMarkers = markers.filter((marker) => markerMatchesSource(marker, source));
    const pinScale = Math.max(modelScale * 0.045, 0.12);
    const pinOffset = Math.max(modelScale * 0.006, 0.02);

    sourceMarkers.forEach((marker) => {
        const position = vectorFromPayload(marker.position);

        if (!position) {
            return;
        }

        const normal = vectorFromPayload(marker.normal) || new THREE.Vector3(0, 1, 0);
        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
            map: markerTexture(marker.severity),
            transparent: true,
            depthTest: false,
            depthWrite: false,
        }));

        sprite.name = `issue-marker-${marker.id}`;
        sprite.renderOrder = 20;
        sprite.scale.set(pinScale, pinScale, pinScale);
        sprite.position.copy(position).add(normal.clone().normalize().multiplyScalar(pinOffset));
        sprite.userData.markerId = String(marker.id);

        scene.add(sprite);
        markerPickableObjects.push(sprite);
        markerMeshesById.set(String(marker.id), {
            marker,
            object: sprite,
            baseScale: pinScale,
        });
    });
}

function markerTexture(severity) {
    const color = severityColor(severity);

    if (markerTextureCache.has(color)) {
        return markerTextureCache.get(color);
    }

    const canvas = document.createElement('canvas');
    canvas.width = 96;
    canvas.height = 96;
    const context = canvas.getContext('2d');

    context.clearRect(0, 0, canvas.width, canvas.height);
    context.beginPath();
    context.arc(48, 48, 34, 0, Math.PI * 2);
    context.fillStyle = color;
    context.fill();
    context.lineWidth = 8;
    context.strokeStyle = '#ffffff';
    context.stroke();

    context.beginPath();
    context.arc(48, 48, 15, 0, Math.PI * 2);
    context.fillStyle = 'rgba(255, 255, 255, 0.92)';
    context.fill();

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    markerTextureCache.set(color, texture);

    return texture;
}

function severityColor(severity) {
    switch (severity) {
        case 'critical':
            return '#991b1b';
        case 'high':
            return '#ef4444';
        case 'low':
            return '#16a34a';
        case 'medium':
        default:
            return '#f59e0b';
    }
}

function highlightMarkerPin(markerMeshesById, markerId) {
    const activeMarkerId = String(markerId);

    markerMeshesById.forEach((entry, id) => {
        const scale = entry.baseScale * (id === activeMarkerId ? 1.35 : 1);
        entry.object.scale.set(scale, scale, scale);
    });
}

function showMarkerTooltip(container, marker) {
    let tooltip = container.querySelector('[data-marker-tooltip]');

    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.className = 'twin-marker-tooltip';
        tooltip.dataset.markerTooltip = 'true';
        container.appendChild(tooltip);
    }

    const meta = [
        marker.roomName,
        marker.surfaceLabel,
        marker.pharFindingId ? `PHAR #${marker.pharFindingId}` : 'Needs PHAR',
    ].filter(Boolean).join(' / ');

    tooltip.innerHTML = `
        <strong>${escapeHtml(marker.title || 'Issue marker')}</strong>
        ${meta ? `<div>${escapeHtml(meta)}</div>` : ''}
        ${marker.description ? `<div class="mt-1">${escapeHtml(marker.description)}</div>` : ''}
    `;
}

function vectorFromPayload(payload) {
    if (!payload || typeof payload !== 'object') {
        return null;
    }

    const x = Number(payload.x);
    const y = Number(payload.y);
    const z = Number(payload.z);

    if (![x, y, z].every(Number.isFinite)) {
        return null;
    }

    return new THREE.Vector3(x, y, z);
}

function markerMatchesSource(marker, source) {
    const markerModelId = Number(marker.spatialModelId || 0);
    const sourceModelId = Number(source.spatialModelId || 0);

    if (markerModelId && sourceModelId) {
        return markerModelId === sourceModelId;
    }

    const markerCaptureId = Number(marker.captureSessionId || 0);
    const sourceCaptureId = Number(source.captureSessionId || 0);

    return markerCaptureId && sourceCaptureId && markerCaptureId === sourceCaptureId;
}

function sourceForMarker(sources, marker) {
    if (!marker) {
        return null;
    }

    const matches = sources.filter((source) => markerMatchesSource(marker, source));

    return matches.find((source) => ['three_model', 'hosted_tour'].includes(source.viewerType))
        || matches[0]
        || null;
}

function currentMarkerFilter(root) {
    return root?.dataset?.markerFilter || 'all';
}

function markerMatchesFilter(marker, filter) {
    switch (filter) {
        case 'phar':
            return Boolean(marker.pharFindingId);
        case 'unlinked':
            return !marker.pharFindingId;
        case 'critical':
        case 'high':
        case 'medium':
        case 'low':
            return marker.severity === filter;
        case 'all':
        default:
            return true;
    }
}

function cardMatchesFilter(card, filter) {
    switch (filter) {
        case 'phar':
            return card.dataset.hasPhar === '1';
        case 'unlinked':
            return card.dataset.hasPhar !== '1';
        case 'critical':
        case 'high':
        case 'medium':
        case 'low':
            return card.dataset.severity === filter;
        case 'all':
        default:
            return true;
    }
}

function disposeScene(scene) {
    scene.traverse((object) => {
        if (object.geometry) {
            object.geometry.dispose();
        }

        if (object.material) {
            const materials = Array.isArray(object.material) ? object.material : [object.material];
            materials.forEach((material) => {
                Object.values(material).forEach((value) => {
                    if (value && typeof value.dispose === 'function') {
                        value.dispose();
                    }
                });
                material.dispose();
            });
        }
    });
}

function addPlacementHint(container) {
    const hint = document.createElement('div');
    hint.className = 'twin-placement-hint';
    hint.dataset.placementHint = 'true';
    hint.textContent = 'Click a surface on this 3D model to fill the issue marker coordinates.';
    container.appendChild(hint);
}

function updatePlacementHint(container, point) {
    const hint = container.querySelector('[data-placement-hint]');

    if (!hint) {
        return;
    }

    hint.classList.add('is-selected');
    hint.textContent = `Marker position selected: X ${formatCoordinate(point.x)}, Y ${formatCoordinate(point.y)}, Z ${formatCoordinate(point.z)}. Complete the issue title and save the marker.`;
}

function placeMarkerPin(container, existingPin, x, y) {
    const pin = existingPin || document.createElement('div');
    pin.className = 'twin-placement-pin';
    pin.style.left = `${x}px`;
    pin.style.top = `${y}px`;

    if (!existingPin) {
        container.appendChild(pin);
    }

    return pin;
}

function fillMarkerForm(source, point, normal, camera = null, controls = null, object = null) {
    const form = document.querySelector('[data-issue-marker-form]');

    if (!form) {
        return;
    }

    setField(form, 'spatial_model_id', source.spatialModelId || source.id?.replace('model-', '') || '');
    setField(form, 'capture_session_id', source.captureSessionId || '');
    setField(form, 'position_x', formatCoordinate(point.x));
    setField(form, 'position_y', formatCoordinate(point.y));
    setField(form, 'position_z', formatCoordinate(point.z));
    setField(form, 'normal_x', formatNormal(normal.x));
    setField(form, 'normal_y', formatNormal(normal.y));
    setField(form, 'normal_z', formatNormal(normal.z));
    setField(form, 'camera_position', vectorToJson(camera?.position));
    setField(form, 'camera_target', vectorToJson(controls?.target));
    setField(form, 'object_uuid', object?.uuid || '');

    const sourceReference = [
        source.title,
        source.runtimeFormat || source.originalFormat,
    ].filter(Boolean).join(' / ');

    if (sourceReference) {
        setField(form, 'source_reference', sourceReference, false);
    }

    const sourceProvider = form.querySelector('[name="source_provider"]');
    if (sourceProvider && source.provider && [...sourceProvider.options].some((option) => option.value === source.provider)) {
        sourceProvider.value = source.provider;
        sourceProvider.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const title = form.querySelector('[name="title"]');
    if (title && !title.value) {
        title.focus({ preventScroll: false });
    }
}

function setField(form, name, value, overwrite = true) {
    const field = form.querySelector(`[name="${name}"]`);

    if (!field || (!overwrite && field.value)) {
        return;
    }

    field.value = value ?? '';
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
}

function formatCoordinate(value) {
    return Number(value || 0).toFixed(4);
}

function formatNormal(value) {
    return Number(value || 0).toFixed(6);
}

function vectorToJson(vector) {
    if (!vector) {
        return '';
    }

    return JSON.stringify({
        x: Number(vector.x || 0).toFixed(4),
        y: Number(vector.y || 0).toFixed(4),
        z: Number(vector.z || 0).toFixed(4),
    });
}

function renderSource(root, stage, source, markers = [], options = {}) {
    clearStage(stage);
    viewerControllers.set(root, emptyViewerController(root));
    setActiveButton(root, source.id);
    updateSourceActions(root, source);

    switch (source.viewerType) {
        case 'awaiting_processing':
            renderCard(stage, source, source.title, 'This source is preserved, but it is awaiting processing before it can be opened in the browser viewer.', 'Open Source File');
            break;
        case 'hosted_tour':
            renderHostedTour(stage, source);
            break;
        case 'three_model':
            renderThreeModel(stage, source, root, markers, options);
            break;
        case 'panorama':
            renderPanorama(stage, source);
            break;
        case 'image':
            renderImage(stage, source);
            break;
        case 'pdf':
            renderPdf(stage, source);
            break;
        case 'media_gallery':
            renderMediaGallery(stage, source);
            break;
        case 'potree':
            renderPotree(stage, source);
            break;
        case 'point_cloud_preview':
            renderPointCloudPreview(stage, source);
            break;
        case 'external_link':
            renderCard(stage, source, source.title, 'This source is available as an external link.', 'Open External Source');
            break;
        default:
            renderCard(stage, source, source.title, 'This source is stored as evidence. Add a GLB/glTF, image, PDF, panorama, or hosted tour URL to preview it in the browser.', 'Open Source File');
    }

    if (options.focusMarkerId) {
        setActiveMarker(root, options.focusMarkerId);
    } else {
        setActiveMarker(root, null);
    }

    applyMarkerFilter(root);
}

function initViewer(root) {
    const sources = parseSources(root);
    const markers = parseMarkers(root);
    const stage = root.querySelector('[data-twin-stage]');

    if (!stage || sources.length === 0) {
        return;
    }

    root.dataset.markerFilter = root.dataset.markerFilter || 'all';

    const openSource = (source, options = {}) => {
        renderSource(root, stage, source, markers, options);
    };

    root.querySelectorAll('[data-twin-source-button]').forEach((button) => {
        button.addEventListener('click', () => {
            const source = sources.find((candidate) => candidate.id === button.dataset.twinSourceButton);
            if (source) {
                openSource(source);
            }
        });
    });

    root.querySelectorAll('[data-twin-marker-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            root.dataset.markerFilter = button.dataset.twinMarkerFilter || 'all';
            applyMarkerFilter(root);
        });
    });

    root.querySelectorAll('[data-twin-marker-card]').forEach((card) => {
        card.addEventListener('click', () => {
            const marker = markers.find((candidate) => String(candidate.id) === card.dataset.markerId);

            if (!marker) {
                return;
            }

            const nextSource = sourceForMarker(sources, marker);

            if (!nextSource) {
                setActiveMarker(root, marker.id);
                return;
            }

            if (root.dataset.currentSource === nextSource.id) {
                const controller = viewerControllers.get(root);

                if (controller && typeof controller.focusMarker === 'function' && controller.focusMarker(marker.id)) {
                    return;
                }
            }

            openSource(nextSource, { focusMarkerId: marker.id });
        });
    });

    const initialId = root.dataset.initialSource;
    const initialSource = sources.find((source) => source.id === initialId) || sources[0];
    openSource(initialSource);
}

function emptyViewerController(root) {
    return {
        focusMarker(markerId) {
            setActiveMarker(root, markerId);
            return false;
        },
        setFilter() {},
    };
}

function escapeHtml(value) {
    return String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replaceAll('`', '&#096;');
}

function cssEscape(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
        return window.CSS.escape(value);
    }

    return String(value).replace(/["\\]/g, '\\$&');
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-digital-twin-viewer]').forEach(initViewer);
});
