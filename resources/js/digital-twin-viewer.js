import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

const viewerDisposers = new WeakMap();

function parseSources(root) {
    const script = root.querySelector('[data-twin-sources]');

    if (!script) {
        return [];
    }

    try {
        const parsed = JSON.parse(script.textContent || '[]');
        return Array.isArray(parsed) ? parsed : Object.values(parsed);
    } catch (error) {
        console.warn('Unable to parse digital twin sources', error);
        return [];
    }
}

function sourceUrl(source) {
    return source.fileUrl || source.externalUrl || source.downloadUrl || '';
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
    root.querySelectorAll('[data-twin-source-button]').forEach((button) => {
        button.classList.toggle('is-active', button.dataset.twinSourceButton === sourceId);
    });
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

function renderThreeModel(stage, source) {
    const url = source.fileUrl || source.externalUrl;

    if (!url) {
        renderCard(stage, source, source.title, 'This 3D model source has no GLB or glTF file yet.');
        return;
    }

    const container = document.createElement('div');
    container.className = 'twin-three-stage';
    stage.appendChild(container);

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x07111f);

    const camera = new THREE.PerspectiveCamera(55, 1, 0.01, 10000);
    camera.position.set(3, 2.2, 4);

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    container.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;

    const raycaster = new THREE.Raycaster();
    const pointer = new THREE.Vector2();
    const pickableObjects = [];
    let pointerDown = null;
    let markerPin = null;

    const ambient = new THREE.HemisphereLight(0xffffff, 0x334155, 1.6);
    scene.add(ambient);

    const keyLight = new THREE.DirectionalLight(0xffffff, 2);
    keyLight.position.set(4, 6, 5);
    scene.add(keyLight);

    const grid = new THREE.GridHelper(10, 20, 0x4b5563, 0x1f2937);
    grid.position.y = -0.01;
    scene.add(grid);

    let frameId = null;
    let disposed = false;
    const resizeObserver = new ResizeObserver(resize);

    const loader = new GLTFLoader();
    loader.load(
        url,
        (gltf) => {
            const model = gltf.scene;
            scene.add(model);
            model.traverse((object) => {
                if (object.isMesh) {
                    pickableObjects.push(object);
                }
            });
            frameObject(model, camera, controls);
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

        controls.update();
        renderer.render(scene, camera);
        frameId = window.requestAnimationFrame(animate);
    }

    function cleanup() {
        disposed = true;
        resizeObserver.disconnect();
        renderer.domElement.removeEventListener('pointerdown', handlePointerDown);
        renderer.domElement.removeEventListener('pointerup', handlePointerUp);
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
    renderer.domElement.addEventListener('pointerdown', handlePointerDown);
    renderer.domElement.addEventListener('pointerup', handlePointerUp);
    animate();

    viewerDisposers.set(stage, cleanup);

    function handlePointerDown(event) {
        pointerDown = {
            x: event.clientX,
            y: event.clientY,
            time: Date.now(),
        };
    }

    function handlePointerUp(event) {
        if (!pointerDown || event.button !== 0 || pickableObjects.length === 0) {
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

        const [hit] = raycaster.intersectObjects(pickableObjects, true);

        if (!hit) {
            return;
        }

        const worldNormal = hit.face?.normal
            ? hit.face.normal.clone().transformDirection(hit.object.matrixWorld).normalize()
            : new THREE.Vector3(0, 1, 0);

        fillMarkerForm(source, hit.point, worldNormal);
        markerPin = placeMarkerPin(container, markerPin, event.clientX - rect.left, event.clientY - rect.top);
        updatePlacementHint(container, hit.point);
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

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
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
    const distance = maxDimension * 1.8;

    object.position.sub(center);
    camera.position.set(distance, distance * 0.7, distance);
    camera.near = Math.max(distance / 100, 0.01);
    camera.far = distance * 100;
    camera.updateProjectionMatrix();
    controls.target.set(0, 0, 0);
    controls.update();
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

function fillMarkerForm(source, point, normal) {
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

function renderSource(root, stage, source) {
    clearStage(stage);
    setActiveButton(root, source.id);

    switch (source.viewerType) {
        case 'hosted_tour':
            renderHostedTour(stage, source);
            break;
        case 'three_model':
            renderThreeModel(stage, source);
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
        case 'potree':
            renderPotree(stage, source);
            break;
        case 'external_link':
            renderCard(stage, source, source.title, 'This source is available as an external link.', 'Open External Source');
            break;
        default:
            renderCard(stage, source, source.title, 'This source is stored as evidence. Add a GLB/glTF, image, PDF, panorama, or hosted tour URL to preview it in the browser.', 'Open Source File');
    }
}

function initViewer(root) {
    const sources = parseSources(root);
    const stage = root.querySelector('[data-twin-stage]');

    if (!stage || sources.length === 0) {
        return;
    }

    root.querySelectorAll('[data-twin-source-button]').forEach((button) => {
        button.addEventListener('click', () => {
            const source = sources.find((candidate) => candidate.id === button.dataset.twinSourceButton);
            if (source) {
                renderSource(root, stage, source);
            }
        });
    });

    const initialId = root.dataset.initialSource;
    const initialSource = sources.find((source) => source.id === initialId) || sources[0];
    renderSource(root, stage, initialSource);
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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-digital-twin-viewer]').forEach(initViewer);
});
