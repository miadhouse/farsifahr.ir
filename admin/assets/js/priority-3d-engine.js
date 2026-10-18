/**
 * FarsiFahr 3D Intersection & Right-of-Way Engine (Three.js)
 * Renders a 3/4 Top-Down (Elevated Isometric Perspective) 3D Intersection
 * with 3D Vehicles, Pedestrians, Stacked Traffic Signs, Traffic Lights,
 * 3D Trajectory Arrows, Raycasting, and Smooth Curve Animations.
 */
(function (window) {
    'use strict';

    const INTERSECTION_BRANCHES = {
        cross4: ['top', 'right', 'bottom', 'left'],
        t_no_top: ['right', 'bottom', 'left'],
        t_no_bottom: ['top', 'right', 'left'],
        t_no_left: ['top', 'right', 'bottom'],
        t_no_right: ['top', 'bottom', 'left']
    };

    const VEHICLE_SPECS = {
        car_yellow: { name: 'خودرو زرد', color: 0xfacc15, roof: 0xfef08a, kind: 'car' },
        car_green:  { name: 'خودرو سبز', color: 0x22c55e, roof: 0x86efac, kind: 'car' },
        car_blue:   { name: 'خودرو آبی', color: 0x3b82f6, roof: 0x93c5fd, kind: 'car' },
        car_red:    { name: 'خودرو قرمز', color: 0xef4444, roof: 0xfca5a5, kind: 'car' },
        bus:        { name: 'اتوبوس', color: 0xf97316, roof: 0xfdba74, kind: 'bus' },
        truck:      { name: 'کامیون', color: 0x64748b, roof: 0xe2e8f0, kind: 'truck' },
        moto:       { name: 'موتورسیکلت', color: 0xec4899, roof: 0xfde047, kind: 'moto' },
        bike:       { name: 'دوچرخه', color: 0x06b6d4, roof: 0x67e8f9, kind: 'bike' },
        scooter:    { name: 'اسکوتر برقی', color: 0xa855f7, roof: 0xd8b4fe, kind: 'scooter' },
        police:     { name: 'خودرو پلیس/امداد', color: 0x1d4ed8, roof: 0xffffff, kind: 'police' }
    };

    const PEDESTRIAN_SPECS = {
        pedestrian:  { name: 'عابر پیاده', color: 0x10b981, kind: 'walker' },
        ped_child:   { name: 'کودک در پیاده‌رو', color: 0xf43f5e, kind: 'child' },
        ped_bike:    { name: 'دوچرخه در گذرگاه', color: 0x0ea5e9, kind: 'cyclist' },
        ped_scooter: { name: 'اسکوتر در پیاده‌رو', color: 0x8b5cf6, kind: 'scooter' }
    };

    /**
     * 3D World Coordinates (Center of intersection = (0, 0, 0))
     * Road width = 10 units (-5 to +5), Lane center offset = 2.4 units
     * top = North (-Z), bottom = South (+Z), right = East (+X), left = West (-X)
     */
    const BRANCH_3D = {
        bottom: {
            vehicleStart: { x: 2.4, z: 12.2, rotY: Math.PI },       // Facing -Z (North)
            pedStart:     { x: 5.8, z: 6.8,  rotY: -Math.PI / 2 },  // Walking West (-X)
            pedEnd:       { x: -5.8, z: 6.8 },
            signSlot:     { x: 6.6, z: 8.6,  rotY: 0 },             // Facing South (+Z)
            dropZones: {
                vehicle:    { x: 2.4, y: 0.8, z: 12.2 },
                pedestrian: { x: 5.8, y: 0.8, z: 6.8 },
                sign:       { x: 6.6, y: 2.5, z: 8.6 }
            }
        },
        top: {
            vehicleStart: { x: -2.4, z: -12.2, rotY: 0 },           // Facing +Z (South)
            pedStart:     { x: -5.8, z: -6.8,  rotY: Math.PI / 2 }, // Walking East (+X)
            pedEnd:       { x: 5.8,  z: -6.8 },
            signSlot:     { x: -6.6, z: -8.6,  rotY: Math.PI },     // Facing North (-Z)
            dropZones: {
                vehicle:    { x: -2.4, y: 0.8, z: -12.2 },
                pedestrian: { x: -5.8, y: 0.8, z: -6.8 },
                sign:       { x: -6.6, y: 2.5, z: -8.6 }
            }
        },
        right: {
            vehicleStart: { x: 12.2, z: -2.4, rotY: -Math.PI / 2 }, // Facing -X (West)
            pedStart:     { x: 6.8,  z: -5.8, rotY: 0 },            // Walking South (+Z)
            pedEnd:       { x: 6.8,  z: 5.8 },
            signSlot:     { x: 8.6,  z: -6.6, rotY: Math.PI / 2 },  // Facing East (+X)
            dropZones: {
                vehicle:    { x: 12.2, y: 0.8, z: -2.4 },
                pedestrian: { x: 6.8,  y: 0.8, z: -5.8 },
                sign:       { x: 8.6,  y: 2.5, z: -6.6 }
            }
        },
        left: {
            vehicleStart: { x: -12.2, z: 2.4, rotY: Math.PI / 2 },  // Facing +X (East)
            pedStart:     { x: -6.8,  z: 5.8, rotY: Math.PI },      // Walking North (-Z)
            pedEnd:       { x: -6.8,  z: -5.8 },
            signSlot:     { x: -8.6,  z: 6.6, rotY: -Math.PI / 2 }, // Facing West (-X)
            dropZones: {
                vehicle:    { x: -12.2, y: 0.8, z: 2.4 },
                pedestrian: { x: -6.8,  y: 0.8, z: 5.8 },
                sign:       { x: -8.6,  y: 2.5, z: 6.6 }
            }
        }
    };

    /**
     * Draw crisp German StVO traffic sign on an HTML5 Canvas and return THREE.CanvasTexture
     */
    const signTextureCache = {};
    function getSignCanvasTexture(signId) {
        if (signTextureCache[signId]) return signTextureCache[signId];

        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 256;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, 256, 256);

        ctx.save();
        ctx.translate(128, 128);

        if (signId === 's306_priority_road' || signId === 's307_end_priority') {
            ctx.rotate(Math.PI / 4);
            ctx.fillStyle = '#ffffff';
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 10;
            ctx.fillRect(-78, -78, 156, 156);
            ctx.strokeRect(-78, -78, 156, 156);

            ctx.fillStyle = '#facc15';
            ctx.fillRect(-52, -52, 104, 104);
            ctx.lineWidth = 6;
            ctx.strokeRect(-52, -52, 104, 104);

            if (signId === 's307_end_priority') {
                ctx.strokeStyle = '#0f172a';
                ctx.lineWidth = 16;
                ctx.beginPath();
                ctx.moveTo(-70, -70);
                ctx.lineTo(70, 70);
                ctx.stroke();
            }
        } else if (signId === 's205_yield') {
            ctx.beginPath();
            ctx.moveTo(-102, -82);
            ctx.lineTo(102, -82);
            ctx.lineTo(0, 96);
            ctx.closePath();
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.lineWidth = 24;
            ctx.strokeStyle = '#dc2626';
            ctx.lineJoin = 'round';
            ctx.stroke();
        } else if (signId === 's206_stop') {
            const r = 104;
            ctx.beginPath();
            for (let i = 0; i < 8; i++) {
                const a = (i * Math.PI / 4) + (Math.PI / 8);
                const x = Math.cos(a) * r;
                const y = Math.sin(a) * r;
                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            }
            ctx.closePath();
            ctx.fillStyle = '#dc2626';
            ctx.fill();
            ctx.lineWidth = 12;
            ctx.strokeStyle = '#ffffff';
            ctx.stroke();

            ctx.fillStyle = '#ffffff';
            ctx.font = '900 58px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('STOP', 0, 4);
        } else if (signId === 's301_priority_next' || signId === 's102_rvl') {
            ctx.beginPath();
            ctx.moveTo(0, -96);
            ctx.lineTo(102, 84);
            ctx.lineTo(-102, 84);
            ctx.closePath();
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.lineWidth = 24;
            ctx.strokeStyle = '#dc2626';
            ctx.lineJoin = 'round';
            ctx.stroke();

            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 16;
            ctx.lineCap = 'round';
            if (signId === 's102_rvl') {
                ctx.beginPath();
                ctx.moveTo(-32, 0); ctx.lineTo(32, 54);
                ctx.moveTo(32, 0);  ctx.lineTo(-32, 54);
                ctx.stroke();
            } else {
                ctx.beginPath();
                ctx.moveTo(0, 58); ctx.lineTo(0, -24);
                ctx.moveTo(-26, 4); ctx.lineTo(0, -28); ctx.lineTo(26, 4);
                ctx.stroke();
            }
        } else if (signId === 's350_crosswalk') {
            ctx.fillStyle = '#2563eb';
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 12;
            ctx.fillRect(-92, -92, 184, 184);
            ctx.strokeRect(-92, -92, 184, 184);

            ctx.beginPath();
            ctx.moveTo(0, -68);
            ctx.lineTo(74, 62);
            ctx.lineTo(-74, 62);
            ctx.closePath();
            ctx.fillStyle = '#ffffff';
            ctx.fill();

            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 12;
            ctx.setLineDash([14, 10]);
            ctx.beginPath();
            ctx.moveTo(-45, 42);
            ctx.lineTo(45, 42);
            ctx.stroke();
        } else if (signId.startsWith('abk_') || signId === 'rad_cross') {
            ctx.fillStyle = '#ffffff';
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 12;
            ctx.fillRect(-92, -74, 184, 148);
            ctx.strokeRect(-92, -74, 184, 148);

            if (signId === 'rad_cross') {
                ctx.fillStyle = '#0f172a';
                ctx.font = '900 44px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('🚲 ↔', 0, 0);
            } else {
                // Thin side roads
                ctx.strokeStyle = '#94a3b8';
                ctx.lineWidth = 12;
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(0, -54); ctx.lineTo(0, 54);
                ctx.moveTo(-64, 0); ctx.lineTo(64, 0);
                ctx.stroke();

                // Thick priority road curve
                ctx.strokeStyle = '#0f172a';
                ctx.lineWidth = 26;
                ctx.lineJoin = 'round';
                ctx.beginPath();
                if (signId === 'abk_bottom_left') {
                    ctx.moveTo(0, 54); ctx.lineTo(0, 0); ctx.lineTo(-64, 0);
                } else if (signId === 'abk_bottom_right') {
                    ctx.moveTo(0, 54); ctx.lineTo(0, 0); ctx.lineTo(64, 0);
                } else if (signId === 'abk_top_left') {
                    ctx.moveTo(0, -54); ctx.lineTo(0, 0); ctx.lineTo(-64, 0);
                } else if (signId === 'abk_top_right') {
                    ctx.moveTo(0, -54); ctx.lineTo(0, 0); ctx.lineTo(64, 0);
                }
                ctx.stroke();
            }
        }

        ctx.restore();

        const tex = new THREE.CanvasTexture(canvas);
        tex.needsUpdate = true;
        signTextureCache[signId] = tex;
        return tex;
    }

    /**
     * Build a 3D Vehicle Mesh Group (oriented facing +Z in local space)
     */
    function create3DVehicleMesh(typeId, direction) {
        const spec = VEHICLE_SPECS[typeId] || VEHICLE_SPECS.car_yellow;
        const group = new THREE.Group();

        const wheelGeo = new THREE.CylinderGeometry(0.42, 0.42, 0.32, 16);
        wheelGeo.rotateZ(Math.PI / 2);
        const wheelMat = new THREE.MeshStandardMaterial({ color: 0x111827, roughness: 0.8 });

        const bodyMat = new THREE.MeshStandardMaterial({ color: spec.color, roughness: 0.35, metalness: 0.15 });
        const roofMat = new THREE.MeshStandardMaterial({ color: spec.roof, roughness: 0.3 });
        const glassMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8, roughness: 0.15, metalness: 0.6 });
        const headlightMat = new THREE.MeshStandardMaterial({ color: 0xfef9c3, emissive: 0xfef08a, emissiveIntensity: 0.8 });
        const taillightMat = new THREE.MeshStandardMaterial({ color: 0xef4444, emissive: 0xdc2626, emissiveIntensity: 0.6 });

        if (spec.kind === 'bus') {
            const hull = new THREE.Mesh(new THREE.BoxGeometry(2.3, 1.65, 5.4), bodyMat);
            hull.position.y = 1.05;
            hull.castShadow = true;
            group.add(hull);

            const roof = new THREE.Mesh(new THREE.BoxGeometry(2.1, 0.18, 4.8), roofMat);
            roof.position.y = 1.92;
            group.add(roof);

            const frontGlass = new THREE.Mesh(new THREE.BoxGeometry(2.05, 0.75, 0.12), glassMat);
            frontGlass.position.set(0, 1.25, 2.68);
            group.add(frontGlass);

            const sideGlass = new THREE.Mesh(new THREE.BoxGeometry(2.34, 0.65, 4.4), glassMat);
            sideGlass.position.set(0, 1.3, 0);
            group.add(sideGlass);

            [[-1.1, -1.8], [1.1, -1.8], [-1.1, 1.8], [1.1, 1.8]].forEach(([wx, wz]) => {
                const w = new THREE.Mesh(wheelGeo, wheelMat);
                w.position.set(wx, 0.42, wz);
                group.add(w);
            });
        } else if (spec.kind === 'truck') {
            // Cargo box
            const cargoMat = new THREE.MeshStandardMaterial({ color: 0xf1f5f9, roughness: 0.45 });
            const cargo = new THREE.Mesh(new THREE.BoxGeometry(2.35, 1.95, 3.4), cargoMat);
            cargo.position.set(0, 1.25, -0.75);
            cargo.castShadow = true;
            group.add(cargo);

            // Cab
            const cab = new THREE.Mesh(new THREE.BoxGeometry(2.2, 1.5, 1.6), bodyMat);
            cab.position.set(0, 1.0, 1.85);
            cab.castShadow = true;
            group.add(cab);

            const windshield = new THREE.Mesh(new THREE.BoxGeometry(1.95, 0.6, 0.12), glassMat);
            windshield.position.set(0, 1.25, 2.63);
            group.add(windshield);

            [[-1.1, -1.7], [1.1, -1.7], [-1.1, 1.8], [1.1, 1.8]].forEach(([wx, wz]) => {
                const w = new THREE.Mesh(wheelGeo, wheelMat);
                w.position.set(wx, 0.42, wz);
                group.add(w);
            });
        } else if (spec.kind === 'moto' || spec.kind === 'bike' || spec.kind === 'scooter') {
            const frame = new THREE.Mesh(new THREE.BoxGeometry(0.45, 0.65, 2.1), bodyMat);
            frame.position.y = 0.6;
            frame.castShadow = true;
            group.add(frame);

            const handle = new THREE.Mesh(new THREE.CylinderGeometry(0.07, 0.07, 1.2, 8), wheelMat);
            handle.rotation.z = Math.PI / 2;
            handle.position.set(0, 1.15, 0.55);
            group.add(handle);

            // Rider torso & helmet
            const rider = new THREE.Mesh(new THREE.CylinderGeometry(0.32, 0.36, 0.85, 12), bodyMat);
            rider.position.set(0, 1.2, -0.1);
            rider.castShadow = true;
            group.add(rider);

            const helmetMat = new THREE.MeshStandardMaterial({ color: 0xfde047, roughness: 0.2 });
            const helmet = new THREE.Mesh(new THREE.SphereGeometry(0.34, 16, 16), helmetMat);
            helmet.position.set(0, 1.85, -0.05);
            group.add(helmet);

            [-0.75, 0.75].forEach(wz => {
                const w = new THREE.Mesh(new THREE.CylinderGeometry(0.36, 0.36, 0.2, 14), wheelMat);
                w.rotation.z = Math.PI / 2;
                w.position.set(0, 0.36, wz);
                group.add(w);
            });
        } else {
            // Standard 3D Car (Green, Yellow, Blue, Red, Police)
            const chassis = new THREE.Mesh(new THREE.BoxGeometry(2.05, 0.72, 3.85), bodyMat);
            chassis.position.y = 0.66;
            chassis.castShadow = true;
            chassis.receiveShadow = true;
            group.add(chassis);

            const cabin = new THREE.Mesh(new THREE.BoxGeometry(1.72, 0.66, 2.05), roofMat);
            cabin.position.set(0, 1.32, -0.15);
            cabin.castShadow = true;
            group.add(cabin);

            const windshield = new THREE.Mesh(new THREE.BoxGeometry(1.56, 0.52, 2.12), glassMat);
            windshield.position.set(0, 1.28, -0.15);
            group.add(windshield);

            if (spec.kind === 'police') {
                const barR = new THREE.Mesh(new THREE.BoxGeometry(0.55, 0.2, 0.28), new THREE.MeshStandardMaterial({ color: 0xef4444, emissive: 0xef4444, emissiveIntensity: 0.9 }));
                barR.position.set(-0.32, 1.72, -0.1);
                const barB = new THREE.Mesh(new THREE.BoxGeometry(0.55, 0.2, 0.28), new THREE.MeshStandardMaterial({ color: 0x3b82f6, emissive: 0x3b82f6, emissiveIntensity: 0.9 }));
                barB.position.set(0.32, 1.72, -0.1);
                group.add(barR, barB);
            }

            // Headlights (+Z is front)
            [-0.68, 0.68].forEach(hx => {
                const hl = new THREE.Mesh(new THREE.SphereGeometry(0.18, 10, 10), headlightMat);
                hl.position.set(hx, 0.72, 1.92);
                group.add(hl);

                const tl = new THREE.Mesh(new THREE.BoxGeometry(0.34, 0.18, 0.1), taillightMat);
                tl.position.set(hx, 0.72, -1.92);
                group.add(tl);
            });

            // 4 Wheels
            [[-1.02, -1.18], [1.02, -1.18], [-1.02, 1.18], [1.02, 1.18]].forEach(([wx, wz]) => {
                const w = new THREE.Mesh(wheelGeo, wheelMat);
                w.position.set(wx, 0.42, wz);
                group.add(w);
            });
        }

        // Blinking 3D Turn Signal (راهنمای چشمک‌زن سه‌بعدی در گردش به چپ یا راست)
        if (direction === 'left' || direction === 'right') {
            // In local space (+Z forward), driver's left is +X, driver's right is -X
            const sigX = direction === 'left' ? 0.96 : -0.96;
            const sigZ = spec.kind === 'bus' ? 2.68 : (spec.kind === 'truck' ? 2.62 : 1.92);
            const blinkerMat = new THREE.MeshStandardMaterial({
                color: 0xf97316,
                emissive: 0xf59e0b,
                emissiveIntensity: 1.2
            });
            const blinker = new THREE.Mesh(new THREE.SphereGeometry(0.24, 12, 12), blinkerMat);
            blinker.position.set(sigX, 0.85, sigZ);
            blinker.userData.isBlinker = true;
            group.add(blinker);
        }

        return group;
    }

    /**
     * Build a 3D Pedestrian / Cyclist Mesh Group
     */
    function create3DPedestrianMesh(typeId) {
        const spec = PEDESTRIAN_SPECS[typeId] || PEDESTRIAN_SPECS.pedestrian;
        const group = new THREE.Group();

        const bodyMat = new THREE.MeshStandardMaterial({ color: spec.color, roughness: 0.4 });
        const headMat = new THREE.MeshStandardMaterial({ color: 0xfde047, roughness: 0.3 });

        const scale = spec.kind === 'child' ? 0.78 : 1.0;

        const torso = new THREE.Mesh(new THREE.CylinderGeometry(0.38 * scale, 0.46 * scale, 1.25 * scale, 14), bodyMat);
        torso.position.y = 0.75 * scale;
        torso.castShadow = true;
        group.add(torso);

        const head = new THREE.Mesh(new THREE.SphereGeometry(0.38 * scale, 16, 16), headMat);
        head.position.y = 1.65 * scale;
        head.castShadow = true;
        group.add(head);

        if (spec.kind === 'cyclist' || spec.kind === 'scooter') {
            const wheelMat = new THREE.MeshStandardMaterial({ color: 0x0f172a });
            const base = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.25, 1.45), wheelMat);
            base.position.y = 0.22;
            group.add(base);
        }

        return group;
    }

    /**
     * Build 3D Signpost (Single or Stacked Dual Signs) & 3D Traffic Light for a Branch
     */
    function create3DSignpostAndLightGroup(branchData) {
        const group = new THREE.Group();
        const hasPrimary = branchData.primarySign && branchData.primarySign !== 'none';
        const hasSecondary = branchData.secondarySign && branchData.secondarySign !== 'none';
        const hasLight = branchData.trafficLight && branchData.trafficLight !== 'none';

        if (!hasPrimary && !hasSecondary && !hasLight) return group;

        const poleMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.6, roughness: 0.3 });
        const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.14, 4.8, 12), poleMat);
        pole.position.y = 2.4;
        pole.castShadow = true;
        group.add(pole);

        const base = new THREE.Mesh(new THREE.CylinderGeometry(0.45, 0.55, 0.22, 16), new THREE.MeshStandardMaterial({ color: 0x334155 }));
        base.position.y = 0.11;
        group.add(base);

        // Tilt head slightly toward the 3/4 camera so signs & lights are super readable
        const displayHead = new THREE.Group();
        displayHead.position.y = 3.7;
        displayHead.rotation.x = -0.28;
        group.add(displayHead);

        if (hasPrimary) {
            const tex1 = getSignCanvasTexture(branchData.primarySign);
            const signPlane1 = new THREE.Mesh(
                new THREE.PlaneGeometry(2.1, 2.1),
                new THREE.MeshBasicMaterial({ map: tex1, transparent: true, side: THREE.DoubleSide })
            );
            signPlane1.position.set(0, hasSecondary ? 0.65 : 0.1, 0.16);
            displayHead.add(signPlane1);
        }

        if (hasSecondary) {
            const tex2 = getSignCanvasTexture(branchData.secondarySign);
            const signPlane2 = new THREE.Mesh(
                new THREE.PlaneGeometry(1.85, 1.55),
                new THREE.MeshBasicMaterial({ map: tex2, transparent: true, side: THREE.DoubleSide })
            );
            signPlane2.position.set(0, hasPrimary ? -1.05 : 0.1, 0.16);
            displayHead.add(signPlane2);
        }

        if (hasLight) {
            const lightOffsetX = (hasPrimary || hasSecondary) ? -1.45 : 0;
            const boxGroup = new THREE.Group();
            boxGroup.position.set(lightOffsetX, 0, 0.18);

            const housing = new THREE.Mesh(
                new THREE.BoxGeometry(0.95, 2.35, 0.55),
                new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.5 })
            );
            boxGroup.add(housing);

            const st = branchData.trafficLight;
            const lamps = [
                { y: 0.68, active: st === 'red',    onCol: 0xef4444, offCol: 0x450a0a },
                { y: 0.0,  active: st === 'yellow', onCol: 0xfacc15, offCol: 0x422006 },
                { y: -0.68,active: st === 'green',  onCol: 0x22c55e, offCol: 0x052e16 }
            ];
            lamps.forEach(l => {
                const mat = new THREE.MeshStandardMaterial({
                    color: l.active ? l.onCol : l.offCol,
                    emissive: l.active ? l.onCol : 0x000000,
                    emissiveIntensity: l.active ? 1.4 : 0
                });
                const bulb = new THREE.Mesh(new THREE.SphereGeometry(0.28, 16, 16), mat);
                bulb.position.set(0, l.y, 0.24);
                boxGroup.add(bulb);
            });

            displayHead.add(boxGroup);
        }

        return group;
    }

    /**
     * Build 3D Curve (THREE.CurvePath / QuadraticBezierCurve3 / LineCurve3) for an Actor's Trajectory
     */
    function buildActor3DCurve(actor) {
        const b = actor.branch;
        if (actor.slotType === 'pedestrian') {
            const g = BRANCH_3D[b];
            return new THREE.LineCurve3(
                new THREE.Vector3(g.pedStart.x, 0.18, g.pedStart.z),
                new THREE.Vector3(g.pedEnd.x, 0.18, g.pedEnd.z)
            );
        }

        const start = BRANCH_3D[b].vehicleStart;
        const dir = actor.direction || 'straight';
        const p0 = new THREE.Vector3(start.x, 0.18, start.z);

        if (b === 'bottom') {
            if (dir === 'straight') return new THREE.LineCurve3(p0, new THREE.Vector3(2.4, 0.18, -14.5));
            if (dir === 'right')    return new THREE.QuadraticBezierCurve3(p0, new THREE.Vector3(2.4, 0.18, 2.4), new THREE.Vector3(14.5, 0.18, 2.4));
            if (dir === 'left')     return new THREE.QuadraticBezierCurve3(p0, new THREE.Vector3(2.4, 0.18, -2.4), new THREE.Vector3(-14.5, 0.18, -2.4));
        }
        if (b === 'top') {
            if (dir === 'straight') return new THREE.LineCurve3(p0, new THREE.Vector3(-2.4, 0.18, 14.5));
            if (dir === 'right')    return new THREE.QuadraticBezierCurve3(p0, new THREE.Vector3(-2.4, 0.18, -2.4), new THREE.Vector3(-14.5, 0.18, -2.4));
            if (dir === 'left')     return new THREE.QuadraticBezierCurve3(p0, new THREE.Vector3(-2.4, 0.18, 2.4), new THREE.Vector3(14.5, 0.18, 2.4));
        }
        if (b === 'right') {
            if (dir === 'straight') return new THREE.LineCurve3(p0, new THREE.Vector3(-14.5, 0.18, -2.4));
            if (dir === 'right')    return new THREE.QuadraticBezierCurve3(p0, new THREE.Vector3(2.4, 0.18, -2.4), new THREE.Vector3(2.4, 0.18, -14.5));
            if (dir === 'left')     return new THREE.QuadraticBezierCurve3(p0, new THREE.Vector3(-2.4, 0.18, -2.4), new THREE.Vector3(-2.4, 0.18, 14.5));
        }
        if (b === 'left') {
            if (dir === 'straight') return new THREE.LineCurve3(p0, new THREE.Vector3(14.5, 0.18, 2.4));
            if (dir === 'right')    return new THREE.QuadraticBezierCurve3(p0, new THREE.Vector3(-2.4, 0.18, 2.4), new THREE.Vector3(-2.4, 0.18, 14.5));
            if (dir === 'left')     return new THREE.QuadraticBezierCurve3(p0, new THREE.Vector3(2.4, 0.18, 2.4), new THREE.Vector3(2.4, 0.18, -14.5));
        }
        return new THREE.LineCurve3(p0, new THREE.Vector3(0, 0.18, 0));
    }

    /**
     * Build 3D Dashed Trajectory Ribbon + Arrowhead Cone along a Curve
     */
    function create3DTrajectoryArrowMesh(curve, hexColor) {
        const group = new THREE.Group();
        const mat = new THREE.MeshStandardMaterial({
            color: hexColor,
            emissive: hexColor,
            emissiveIntensity: 0.45,
            roughness: 0.3
        });

        // Create dashed 3D segments along curve
        const segments = 18;
        for (let i = 1; i < segments - 1; i += 2) {
            const t1 = i / segments;
            const t2 = (i + 1) / segments;
            const pt1 = curve.getPointAt(t1);
            const pt2 = curve.getPointAt(t2);
            const segCurve = new THREE.LineCurve3(pt1, pt2);
            const tubeGeo = new THREE.TubeGeometry(segCurve, 4, 0.22, 8, false);
            const mesh = new THREE.Mesh(tubeGeo, mat);
            group.add(mesh);
        }

        // Arrowhead cone at 92% of the curve
        const tipPt = curve.getPointAt(0.92);
        const tangent = curve.getTangentAt(0.92).normalize();
        const coneGeo = new THREE.ConeGeometry(0.62, 1.45, 14);
        coneGeo.rotateX(Math.PI / 2); // Align cone tip with +Z
        const cone = new THREE.Mesh(coneGeo, mat);
        cone.position.copy(tipPt);
        cone.lookAt(tipPt.clone().add(tangent));
        group.add(cone);

        return group;
    }

    /**
     * Main Priority3DScene Controller Class
     */
    class Priority3DScene {
        constructor(containerEl, options = {}) {
            this.container = containerEl;
            this.onActorClick = options.onActorClick || function () {};
            this.onBranchSelect = options.onBranchSelect || function () {};
            this.showDropZones = !!options.showDropZones;

            this.scenarioState = null;
            this.actorsMap = {}; // key -> { actor, mesh, curve, worldBadgePos }
            this.clickedKeys = [];
            this.evaluationResult = null;
            this.activeDropZoneEls = [];
            this.badgeOverlayEls = {};

            this.initThree();
            this.initOverlays();
            this.animateLoop();
        }

        initThree() {
            const w = this.container.clientWidth || 550;
            const h = this.container.clientHeight || 550;

            this.scene = new THREE.Scene();
            this.scene.background = new THREE.Color(0x7dd3fc);

            // 3/4 Elevated Isometric-like Perspective Camera
            this.camera = new THREE.PerspectiveCamera(38, w / h, 1, 200);
            this.setCameraView('iso34');

            this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
            this.renderer.setSize(w, h);
            this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
            this.renderer.shadowMap.enabled = true;
            this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            this.container.innerHTML = '';
            this.container.appendChild(this.renderer.domElement);

            // Lights
            const hemi = new THREE.HemisphereLight(0xffffff, 0x4ade80, 0.78);
            hemi.position.set(0, 50, 0);
            this.scene.add(hemi);

            const dirLight = new THREE.DirectionalLight(0xfffbeb, 0.95);
            dirLight.position.set(22, 38, 24);
            dirLight.castShadow = true;
            dirLight.shadow.mapSize.width = 1024;
            dirLight.shadow.mapSize.height = 1024;
            dirLight.shadow.camera.near = 5;
            dirLight.shadow.camera.far = 95;
            const d = 24;
            dirLight.shadow.camera.left = -d;
            dirLight.shadow.camera.right = d;
            dirLight.shadow.camera.top = d;
            dirLight.shadow.camera.bottom = -d;
            this.scene.add(dirLight);

            this.worldGroup = new THREE.Group();
            this.scene.add(this.worldGroup);

            // Raycaster for clicking 3D vehicles & pedestrians
            this.raycaster = new THREE.Raycaster();
            this.mouse = new THREE.Vector2();

            // Camera drag rotation (right-click or two-finger / subtle drag on background)
            this.isDraggingCam = false;
            this.dragStart = { x: 0, y: 0 };
            this.pointerMoved = false;
            this.camAzimuth = 0.42; // 3/4 angle!
            this.camElevation = 0.88;
            this.camDist = 43;
            this.updateCameraFromSpherical();

            const dom = this.renderer.domElement;
            dom.addEventListener('pointerdown', (e) => {
                this.isDraggingCam = true;
                this.pointerMoved = false;
                this.dragStart = { x: e.clientX, y: e.clientY };
            });

            window.addEventListener('pointermove', (e) => {
                if (!this.isDraggingCam) return;
                const dx = e.clientX - this.dragStart.x;
                const dy = e.clientY - this.dragStart.y;
                if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
                    this.pointerMoved = true;
                }
                if (this.pointerMoved) {
                    this.camAzimuth -= dx * 0.006;
                    this.camElevation = Math.max(0.45, Math.min(1.42, this.camElevation + dy * 0.005));
                    this.dragStart = { x: e.clientX, y: e.clientY };
                    this.updateCameraFromSpherical();
                }
            });

            window.addEventListener('pointerup', (e) => {
                if (!this.isDraggingCam) return;
                this.isDraggingCam = false;
                if (!this.pointerMoved) {
                    this.handleCanvasTap(e);
                }
            });

            window.addEventListener('resize', () => this.resize());
        }

        updateCameraFromSpherical() {
            const x = this.camDist * Math.cos(this.camElevation) * Math.sin(this.camAzimuth);
            const y = this.camDist * Math.sin(this.camElevation);
            const z = this.camDist * Math.cos(this.camElevation) * Math.cos(this.camAzimuth);
            this.camera.position.set(x, y, z);
            this.camera.lookAt(0, 0, 0);
        }

        setCameraView(preset) {
            if (preset === 'iso34') {
                this.camAzimuth = 0.42;
                this.camElevation = 0.88;
                this.camDist = 43;
            } else if (preset === 'isoLeft') {
                this.camAzimuth = -0.45;
                this.camElevation = 0.86;
                this.camDist = 43;
            } else if (preset === 'topDown') {
                this.camAzimuth = 0;
                this.camElevation = 1.45;
                this.camDist = 44;
            } else if (preset === 'driverSouth') {
                this.camAzimuth = 0;
                this.camElevation = 0.52;
                this.camDist = 38;
            }
            if (this.camera) this.updateCameraFromSpherical();
        }

        initOverlays() {
            this.overlayLayer = document.createElement('div');
            this.overlayLayer.style.cssText = 'position:absolute;inset:0;pointer-events:none;overflow:hidden;z-index:5;';
            this.container.appendChild(this.overlayLayer);

            // Camera angle switcher bar inside top-left of 3D stage
            const camBar = document.createElement('div');
            camBar.style.cssText = 'position:absolute;top:10px;left:10px;z-index:10;display:flex;gap:5px;flex-wrap:wrap;';
            camBar.innerHTML = `
                <button type="button" class="btn btn-sm btn-dark border border-warning fw-bold py-1 px-2" style="font-size:0.72rem;opacity:0.9;" data-cam="iso34">نمای سه‌رخ ۳ بعدی</button>
                <button type="button" class="btn btn-sm btn-dark border border-light fw-bold py-1 px-2" style="font-size:0.72rem;opacity:0.85;" data-cam="driverSouth">نمای راننده</button>
                <button type="button" class="btn btn-sm btn-dark border border-light fw-bold py-1 px-2" style="font-size:0.72rem;opacity:0.85;" data-cam="topDown">نمای بالا</button>
            `;
            camBar.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.setCameraView(btn.getAttribute('data-cam'));
                });
            });
            this.container.appendChild(camBar);
        }

        resize() {
            if (!this.container || !this.renderer) return;
            const w = this.container.clientWidth || 550;
            const h = this.container.clientHeight || 550;
            this.camera.aspect = w / h;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(w, h);
        }

        handleCanvasTap(e) {
            const rect = this.renderer.domElement.getBoundingClientRect();
            if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) return;

            this.mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
            this.mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

            this.raycaster.setFromCamera(this.mouse, this.camera);

            const pickables = [];
            Object.values(this.actorsMap).forEach(entry => {
                if (entry.mesh) pickables.push(entry.mesh);
            });

            const hits = this.raycaster.intersectObjects(pickables, true);
            if (hits.length > 0) {
                let obj = hits[0].object;
                while (obj && !obj.userData.actorKey) {
                    obj = obj.parent;
                }
                if (obj && obj.userData.actorKey) {
                    this.onActorClick(obj.userData.actorKey, obj.userData.branch);
                }
            }
        }

        /**
         * Rebuild the entire 3D scene from scenarioState
         */
        renderScenario(scenarioState, clickedKeys = [], evaluationResult = null, showDropZones = false) {
            this.scenarioState = scenarioState;
            this.clickedKeys = clickedKeys || [];
            this.evaluationResult = evaluationResult;
            this.showDropZones = showDropZones;

            // Clear old 3D objects
            while (this.worldGroup.children.length > 0) {
                this.worldGroup.remove(this.worldGroup.children[0]);
            }
            this.overlayLayer.innerHTML = '';
            this.actorsMap = {};
            this.activeDropZoneEls = [];
            this.badgeOverlayEls = {};

            const interType = (scenarioState && scenarioState.intersectionType) || 'cross4';
            const activeBranches = INTERSECTION_BRANCHES[interType] || INTERSECTION_BRANCHES.cross4;

            // 1. 3D Grass Ground
            const grassMat = new THREE.MeshStandardMaterial({ color: 0x4ade80, roughness: 0.9 });
            const grass = new THREE.Mesh(new THREE.BoxGeometry(44, 0.6, 44), grassMat);
            grass.position.y = -0.3;
            grass.receiveShadow = true;
            this.worldGroup.add(grass);

            // 2. Decorative 3D Low-Poly Trees in the 4 outer corners
            [[-13, -13], [13, -13], [-13, 13], [13, 13], [-16, -9], [16, -9], [-16, 9], [16, 9]].forEach(([tx, tz]) => {
                const tree = new THREE.Group();
                const trunk = new THREE.Mesh(new THREE.CylinderGeometry(0.35, 0.45, 1.8, 8), new THREE.MeshStandardMaterial({ color: 0x78350f }));
                trunk.position.y = 0.9;
                trunk.castShadow = true;
                const crown1 = new THREE.Mesh(new THREE.ConeGeometry(1.75, 2.8, 10), new THREE.MeshStandardMaterial({ color: 0x16a34a, roughness: 0.7 }));
                crown1.position.y = 2.6;
                crown1.castShadow = true;
                const crown2 = new THREE.Mesh(new THREE.ConeGeometry(1.35, 2.2, 10), new THREE.MeshStandardMaterial({ color: 0x22c55e, roughness: 0.7 }));
                crown2.position.y = 3.9;
                crown2.castShadow = true;
                tree.add(trunk, crown1, crown2);
                tree.position.set(tx, 0, tz);
                this.worldGroup.add(tree);
            });

            // 3. Sidewalks & Raised Curbs + Asphalt Roads
            const curbMat = new THREE.MeshStandardMaterial({ color: 0xcbd5e1, roughness: 0.7 });
            const roadMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.85 });
            const lineMat = new THREE.MeshBasicMaterial({ color: 0xf8fafc });

            // Center sidewalk base & center intersection asphalt
            const centerSidewalk = new THREE.Mesh(new THREE.BoxGeometry(13.2, 0.22, 13.2), curbMat);
            centerSidewalk.position.y = 0.08;
            centerSidewalk.receiveShadow = true;
            this.worldGroup.add(centerSidewalk);

            const centerAsphalt = new THREE.Mesh(new THREE.BoxGeometry(9.6, 0.24, 9.6), roadMat);
            centerAsphalt.position.y = 0.1;
            centerAsphalt.receiveShadow = true;
            this.worldGroup.add(centerAsphalt);

            const branchConfigs = {
                top:    { sx: 0, sz: -13.5, sw: 13.2, sd: 17, rw: 9.6, rd: 17.5, isVert: true },
                bottom: { sx: 0, sz: 13.5,  sw: 13.2, sd: 17, rw: 9.6, rd: 17.5, isVert: true },
                right:  { sx: 13.5, sz: 0,  sw: 17, sd: 13.2, rw: 17.5, rd: 9.6, isVert: false },
                left:   { sx: -13.5, sz: 0, sw: 17, sd: 13.2, rw: 17.5, rd: 9.6, isVert: false }
            };

            activeBranches.forEach(bName => {
                const cfg = branchConfigs[bName];
                const sw = new THREE.Mesh(new THREE.BoxGeometry(cfg.sw, 0.22, cfg.sd), curbMat);
                sw.position.set(cfg.sx, 0.08, cfg.sz);
                sw.receiveShadow = true;
                this.worldGroup.add(sw);

                const rd = new THREE.Mesh(new THREE.BoxGeometry(cfg.rw, 0.24, cfg.rd), roadMat);
                rd.position.set(cfg.sx, 0.1, cfg.sz);
                rd.receiveShadow = true;
                this.worldGroup.add(rd);

                // Dashed center lane markings
                for (let d = 8.2; d <= 19.5; d += 3.0) {
                    const sign = (bName === 'top' || bName === 'left') ? -1 : 1;
                    const dash = new THREE.Mesh(
                        new THREE.BoxGeometry(cfg.isVert ? 0.22 : 1.6, 0.26, cfg.isVert ? 1.6 : 0.22),
                        lineMat
                    );
                    dash.position.set(cfg.isVert ? 0 : sign * d, 0.11, cfg.isVert ? sign * d : 0);
                    this.worldGroup.add(dash);
                }

                // Crosswalks & Stop/Yield road lines
                const bData = scenarioState.branches ? scenarioState.branches[bName] : null;
                if (bData && bData.hasCrosswalk) {
                    for (let offset = -3.8; offset <= 3.8; offset += 1.1) {
                        const stripe = new THREE.Mesh(
                            new THREE.BoxGeometry(cfg.isVert ? 0.55 : 2.1, 0.26, cfg.isVert ? 2.1 : 0.55),
                            lineMat
                        );
                        if (bName === 'bottom') stripe.position.set(offset, 0.12, 6.8);
                        if (bName === 'top')    stripe.position.set(offset, 0.12, -6.8);
                        if (bName === 'right')  stripe.position.set(6.8, 0.12, offset);
                        if (bName === 'left')   stripe.position.set(-6.8, 0.12, offset);
                        this.worldGroup.add(stripe);
                    }
                }

                if (bData && (bData.primarySign === 's206_stop' || bData.primarySign === 's205_yield')) {
                    const stopLine = new THREE.Mesh(
                        new THREE.BoxGeometry(cfg.isVert ? 4.4 : 0.45, 0.27, cfg.isVert ? 0.45 : 4.4),
                        lineMat
                    );
                    if (bName === 'bottom') stopLine.position.set(2.4, 0.12, 8.2);
                    if (bName === 'top')    stopLine.position.set(-2.4, 0.12, -8.2);
                    if (bName === 'right')  stopLine.position.set(8.2, 0.12, -2.4);
                    if (bName === 'left')   stopLine.position.set(-8.2, 0.12, 2.4);
                    this.worldGroup.add(stopLine);
                }

                // 4. 3D Traffic Signs & Traffic Lights on Branch Corner
                if (bData) {
                    const signGroup = create3DSignpostAndLightGroup(bData);
                    const sPos = BRANCH_3D[bName].signSlot;
                    signGroup.position.set(sPos.x, 0.15, sPos.z);
                    signGroup.rotation.y = sPos.rotY;
                    this.worldGroup.add(signGroup);
                }
            });

            // 5. Trajectories & 3D Vehicles / Pedestrians
            activeBranches.forEach(bName => {
                const bData = scenarioState.branches ? scenarioState.branches[bName] : null;
                if (!bData) return;

                if (bData.vehicle && bData.vehicle.type) {
                    this.spawnActor3D({
                        key: `vehicle_${bName}`,
                        branch: bName,
                        slotType: 'vehicle',
                        type: bData.vehicle.type,
                        direction: bData.vehicle.direction || 'straight',
                        priority: parseInt(bData.vehicle.priority || 1, 10)
                    });
                }

                if (bData.pedestrian && bData.pedestrian.type) {
                    this.spawnActor3D({
                        key: `pedestrian_${bName}`,
                        branch: bName,
                        slotType: 'pedestrian',
                        type: bData.pedestrian.type,
                        direction: 'across',
                        priority: parseInt(bData.pedestrian.priority || 1, 10)
                    });
                }
            });

            // 6. Drop Zone Overlays for Admin Builder Mode
            if (this.showDropZones) {
                const shortBranch = { bottom: 'پایین', top: 'بالا', right: 'راست', left: 'چپ' };
                activeBranches.forEach(bName => {
                    const dz = BRANCH_3D[bName].dropZones;
                    [
                        { slotType: 'vehicle',    pos: dz.vehicle,    label: `خودرو (${shortBranch[bName]})`, icon: 'fa-car' },
                        { slotType: 'pedestrian', pos: dz.pedestrian, label: `پیاده‌رو (${shortBranch[bName]})`, icon: 'fa-walking' },
                        { slotType: 'sign',       pos: dz.sign,       label: `تابلو/چراغ (${shortBranch[bName]})`, icon: 'fa-traffic-light' }
                    ].forEach(slot => {
                        const el = document.createElement('div');
                        el.className = 'drop-slot-group';
                        el.setAttribute('data-branch', bName);
                        el.setAttribute('data-slot-type', slot.slotType);
                        el.style.cssText = `
                            position:absolute;pointer-events:auto;cursor:pointer;
                            transform:translate(-50%,-50%);
                            background:rgba(15,23,42,0.72);color:#fde047;
                            border:2px dashed rgba(250,204,21,0.85);border-radius:10px;
                            padding:3px 7px;font-size:0.68rem;font-weight:900;white-space:nowrap;
                            transition:all 0.15s ease;
                        `;
                        el.innerHTML = `<i class="fas ${slot.icon} me-1"></i>${slot.label}`;
                        el.addEventListener('click', (ev) => {
                            ev.stopPropagation();
                            this.onBranchSelect(bName);
                        });
                        this.overlayLayer.appendChild(el);
                        this.activeDropZoneEls.push({
                            el: el,
                            worldPos: new THREE.Vector3(slot.pos.x, slot.pos.y, slot.pos.z)
                        });
                    });
                });
            }
        }

        spawnActor3D(actor) {
            const curve = buildActor3DCurve(actor);

            // Determine trajectory color (Yellow default; Green/Red after evaluation)
            let trajHex = 0xfacc15;
            if (this.evaluationResult && this.evaluationResult.detailsByKey) {
                const ok = this.evaluationResult.detailsByKey[actor.key];
                trajHex = ok ? 0x22c55e : 0xef4444;
            }

            const trajMesh = create3DTrajectoryArrowMesh(curve, trajHex);
            this.worldGroup.add(trajMesh);

            // Build 3D Mesh
            const mesh = actor.slotType === 'pedestrian'
                ? create3DPedestrianMesh(actor.type)
                : create3DVehicleMesh(actor.type, actor.direction);

            const startSpec = actor.slotType === 'pedestrian'
                ? BRANCH_3D[actor.branch].pedStart
                : BRANCH_3D[actor.branch].vehicleStart;

            if (this.completedMoves && this.completedMoves.has(actor.key)) {
                const pt = curve.getPointAt(0.88);
                const tangent = curve.getTangentAt(0.88).normalize();
                mesh.position.set(pt.x, 0.2, pt.z);
                mesh.lookAt(new THREE.Vector3(pt.x + tangent.x, 0.2, pt.z + tangent.z));
            } else {
                mesh.position.set(startSpec.x, 0.2, startSpec.z);
                mesh.rotation.y = startSpec.rotY;
            }
            mesh.userData.actorKey = actor.key;
            mesh.userData.branch = actor.branch;
            this.worldGroup.add(mesh);

            // Floating HTML Priority / Click Order Badge anchored to 3D vehicle
            const clickIdx = this.clickedKeys.indexOf(actor.key);
            const userOrder = clickIdx !== -1 ? (clickIdx + 1) : null;

            let badgeBg = '#0f172a';
            let badgeBorder = '#fde047';
            let badgeText = this.showDropZones ? actor.priority : (userOrder !== null ? userOrder : '؟');

            if (this.evaluationResult && this.evaluationResult.detailsByKey) {
                const ok = this.evaluationResult.detailsByKey[actor.key];
                badgeBg = ok ? '#16a34a' : '#dc2626';
                badgeBorder = '#ffffff';
                badgeText = this.showDropZones ? `${userOrder || '؟'} (${actor.priority})` : `${userOrder || '؟'}`;
            } else if (userOrder !== null) {
                badgeBg = '#2563eb';
                badgeBorder = '#fde047';
                badgeText = userOrder;
            }

            const badgeEl = document.createElement('div');
            badgeEl.style.cssText = `
                position:absolute;pointer-events:auto;cursor:pointer;
                transform:translate(-50%,-100%);
                background:${badgeBg};color:#ffffff;
                border:2.5px solid ${badgeBorder};border-radius:999px;
                min-width:28px;height:28px;padding:0 7px;
                display:inline-flex;align-items:center;justify-content:center;
                font-weight:900;font-size:0.8rem;box-shadow:0 3px 0 #020617;
            `;
            badgeEl.textContent = badgeText;
            badgeEl.addEventListener('click', (ev) => {
                ev.stopPropagation();
                this.onActorClick(actor.key, actor.branch);
            });
            this.overlayLayer.appendChild(badgeEl);

            this.actorsMap[actor.key] = {
                actor: actor,
                mesh: mesh,
                curve: curve,
                badgeEl: badgeEl,
                startSpec: startSpec,
                animStartTime: null,
                isAnimating: false
            };
        }

        /**
         * Animate a 3D vehicle or pedestrian along its 3D trajectory curve without rebuilding other actors!
         */
        animateActorMovement(actorKey, updatedClickedKeys = null) {
            if (!this.completedMoves) this.completedMoves = new Set();
            this.completedMoves.add(actorKey);

            if (updatedClickedKeys) {
                this.clickedKeys = updatedClickedKeys;
                Object.values(this.actorsMap).forEach(item => {
                    const idx = this.clickedKeys.indexOf(item.actor.key);
                    const ord = idx !== -1 ? (idx + 1) : null;
                    if (ord !== null) {
                        item.badgeEl.style.background = '#2563eb';
                        item.badgeEl.style.borderColor = '#fde047';
                        item.badgeEl.textContent = this.showDropZones ? `${ord} (${item.actor.priority})` : ord;
                    }
                });
            }

            const entry = this.actorsMap[actorKey];
            if (!entry) return;
            entry.animStartTime = performance.now();
            entry.isAnimating = true;
        }

        resetPositions() {
            this.completedMoves = new Set();
            this.clickedKeys = [];
            this.evaluationResult = null;
            Object.values(this.actorsMap).forEach(entry => {
                entry.isAnimating = false;
                if (entry.mesh && entry.startSpec) {
                    entry.mesh.position.set(entry.startSpec.x, 0.2, entry.startSpec.z);
                    entry.mesh.rotation.set(0, entry.startSpec.rotY, 0);
                }
                if (entry.badgeEl) {
                    entry.badgeEl.style.background = '#0f172a';
                    entry.badgeEl.style.borderColor = '#fde047';
                    entry.badgeEl.textContent = this.showDropZones ? entry.actor.priority : '؟';
                }
            });
        }

        updateOverlayProjections() {
            const w = this.renderer.domElement.clientWidth;
            const h = this.renderer.domElement.clientHeight;
            if (!w || !h) return;

            // Project actor badges
            Object.values(this.actorsMap).forEach(entry => {
                if (!entry.mesh || !entry.badgeEl) return;
                const pos = entry.mesh.position.clone();
                pos.y += 2.35;
                pos.project(this.camera);
                const x = (pos.x * 0.5 + 0.5) * w;
                const y = (-(pos.y * 0.5) + 0.5) * h;
                entry.badgeEl.style.left = `${x}px`;
                entry.badgeEl.style.top = `${y}px`;
            });

            // Project builder drop zones
            this.activeDropZoneEls.forEach(item => {
                const pos = item.worldPos.clone().project(this.camera);
                const x = (pos.x * 0.5 + 0.5) * w;
                const y = (-(pos.y * 0.5) + 0.5) * h;
                item.el.style.left = `${x}px`;
                item.el.style.top = `${y}px`;
            });
        }

        animateLoop() {
            requestAnimationFrame(() => this.animateLoop());

            const now = performance.now();
            const blinkOn = Math.floor(now / 380) % 2 === 0;

            // Update vehicle turn signals & active curve movements
            Object.values(this.actorsMap).forEach(entry => {
                if (entry.mesh) {
                    entry.mesh.traverse(child => {
                        if (child.userData && child.userData.isBlinker) {
                            child.visible = blinkOn;
                        }
                    });
                }

                if (entry.isAnimating && entry.curve) {
                    const duration = 1150;
                    const t = Math.min((now - entry.animStartTime) / duration, 1);
                    const eased = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;

                    const u = eased * 0.88;
                    const pt = entry.curve.getPointAt(u);
                    const tangent = entry.curve.getTangentAt(u).normalize();

                    entry.mesh.position.set(pt.x, 0.2, pt.z);
                    const targetLook = new THREE.Vector3(pt.x + tangent.x, 0.2, pt.z + tangent.z);
                    entry.mesh.lookAt(targetLook);

                    if (t >= 1) {
                        entry.isAnimating = false;
                    }
                }
            });

            this.renderer.render(this.scene, this.camera);
            this.updateOverlayProjections();
        }
    }

    window.Priority3DScene = Priority3DScene;
})(window);
