/**
 * FarsiFahr 3D Intersection & Right-of-Way Engine (Three.js)
 * Renders a 3/4 Top-Down (Elevated Isometric Perspective) 3D Intersection
 * with 3D Vehicles, Detailed 3D Pedestrians & Sidewalks, Large Billboarded Traffic Signs,
 * 3D Traffic Lights, 3D Trajectory Arrows, Raycasting, and Smooth Curve Animations.
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
        truck:      { name: 'کامیون آبی', color: 0x1d4ed8, roof: 0x93c5fd, kind: 'truck' },
        moto:       { name: 'موتورسیکلت', color: 0xec4899, roof: 0xfde047, kind: 'moto' },
        bike:       { name: 'دوچرخه', color: 0x06b6d4, roof: 0x67e8f9, kind: 'bike' },
        scooter:    { name: 'اسکوتر برقی', color: 0xa855f7, roof: 0xd8b4fe, kind: 'scooter' },
        police:     { name: 'خودرو پلیس/امداد', color: 0x1d4ed8, roof: 0xffffff, kind: 'police' }
    };

    const PEDESTRIAN_SPECS = {
        pedestrian:  { name: 'عابر پیاده', color: 0x10b981, pants: 0x1e3a8a, hair: 0x451a03, kind: 'walker' },
        ped_child:   { name: 'کودک در پیاده‌رو', color: 0xf43f5e, pants: 0x1e293b, hair: 0x78350f, kind: 'child' },
        ped_bike:    { name: 'دوچرخه در گذرگاه', color: 0x0ea5e9, pants: 0x0f172a, hair: 0x1e293b, kind: 'cyclist' },
        ped_scooter: { name: 'اسکوتر در پیاده‌رو', color: 0x8b5cf6, pants: 0x1e1b4b, hair: 0x312e81, kind: 'scooter' }
    };

    /**
     * 3D World Coordinates (Center of intersection = (0, 0, 0))
     * Road asphalt width = 9.6 units (-4.8 to +4.8), Sidewalks extend from ±4.8 to ±8.6
     * top = North (-Z), bottom = South (+Z), right = East (+X), left = West (-X)
     */
    const BRANCH_3D = {
        bottom: {
            vehicleStart: { x: 2.4, z: 12.6, rotY: Math.PI },       // Facing -Z (North)
            pedStart:     { x: 6.3, z: 6.8,  rotY: -Math.PI / 2 },  // Standing on East sidewalk, walking West (-X)
            pedEnd:       { x: -6.3, z: 6.8 },
            signSlot:     { x: 8.2, z: 10.2, rotY: 0 },             // Outer sidewalk corner
            dropZones: {
                vehicle:    { x: 2.4, y: 0.9, z: 12.6 },
                pedestrian: { x: 6.3, y: 1.2, z: 6.8 },
                sign:       { x: 8.2, y: 3.5, z: 10.2 }
            }
        },
        top: {
            vehicleStart: { x: -2.4, z: -12.6, rotY: 0 },           // Facing +Z (South)
            pedStart:     { x: -6.3, z: -6.8,  rotY: Math.PI / 2 }, // Standing on West sidewalk, walking East (+X)
            pedEnd:       { x: 6.3,  z: -6.8 },
            signSlot:     { x: -8.2, z: -10.2, rotY: Math.PI },     // Outer sidewalk corner
            dropZones: {
                vehicle:    { x: -2.4, y: 0.9, z: -12.6 },
                pedestrian: { x: -6.3, y: 1.2, z: -6.8 },
                sign:       { x: -8.2, y: 3.5, z: -10.2 }
            }
        },
        right: {
            vehicleStart: { x: 12.6, z: -2.4, rotY: -Math.PI / 2 }, // Facing -X (West)
            pedStart:     { x: 6.8,  z: -6.3, rotY: 0 },            // Standing on North sidewalk, walking South (+Z)
            pedEnd:       { x: 6.8,  z: 6.3 },
            signSlot:     { x: 10.2, z: -8.2, rotY: Math.PI / 2 },  // Outer sidewalk corner
            dropZones: {
                vehicle:    { x: 12.6, y: 0.9, z: -2.4 },
                pedestrian: { x: 6.8,  y: 1.2, z: -6.3 },
                sign:       { x: 10.2, y: 3.5, z: -8.2 }
            }
        },
        left: {
            vehicleStart: { x: -12.6, z: 2.4, rotY: Math.PI / 2 },  // Facing +X (East)
            pedStart:     { x: -6.8,  z: 6.3, rotY: Math.PI },      // Standing on South sidewalk, walking North (-Z)
            pedEnd:       { x: -6.8,  z: -6.3 },
            signSlot:     { x: -10.2, z: 8.2, rotY: -Math.PI / 2 }, // Outer sidewalk corner
            dropZones: {
                vehicle:    { x: -12.6, y: 0.9, z: 2.4 },
                pedestrian: { x: -6.8,  y: 1.2, z: 6.3 },
                sign:       { x: -10.2, y: 3.5, z: 8.2 }
            }
        }
    };

    /**
     * Draw high-resolution (512x512) German StVO traffic sign on an HTML5 Canvas
     * with bold strokes and high contrast so it is crystal clear from any distance.
     */
    const signTextureCache = {};
    function getSignCanvasTexture(signId) {
        if (signTextureCache[signId]) return signTextureCache[signId];

        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 512;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, 512, 512);

        ctx.save();
        ctx.translate(256, 256);

        // Subtle outer drop shadow for strong contrast against sky/trees/road
        ctx.shadowColor = 'rgba(2, 6, 23, 0.55)';
        ctx.shadowBlur = 18;
        ctx.shadowOffsetY = 6;

        if (signId === 's306_priority_road' || signId === 's307_end_priority') {
            ctx.rotate(Math.PI / 4);
            // Outer white diamond with thick dark rim
            ctx.fillStyle = '#ffffff';
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 18;
            ctx.fillRect(-162, -162, 324, 324);
            ctx.strokeRect(-162, -162, 324, 324);

            ctx.shadowColor = 'transparent';
            // Inner yellow diamond
            ctx.fillStyle = '#facc15';
            ctx.fillRect(-112, -112, 224, 224);
            ctx.lineWidth = 10;
            ctx.strokeStyle = '#0f172a';
            ctx.strokeRect(-112, -112, 224, 224);

            if (signId === 's307_end_priority') {
                ctx.strokeStyle = '#0f172a';
                ctx.lineWidth = 28;
                ctx.beginPath();
                ctx.moveTo(-148, -148);
                ctx.lineTo(148, 148);
                ctx.stroke();
            }
        } else if (signId === 's205_yield') {
            // Downward triangle (Vorfahrt gewähren)
            ctx.beginPath();
            ctx.moveTo(-215, -175);
            ctx.lineTo(215, -175);
            ctx.lineTo(0, 205);
            ctx.closePath();
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.shadowColor = 'transparent';
            ctx.lineWidth = 52;
            ctx.strokeStyle = '#dc2626';
            ctx.lineJoin = 'round';
            ctx.stroke();
            // Thin outer dark outline for crispness
            ctx.lineWidth = 8;
            ctx.strokeStyle = '#0f172a';
            ctx.stroke();
        } else if (signId === 's206_stop') {
            const r = 222;
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
            ctx.shadowColor = 'transparent';
            ctx.lineWidth = 22;
            ctx.strokeStyle = '#ffffff';
            ctx.stroke();
            ctx.lineWidth = 8;
            ctx.strokeStyle = '#0f172a';
            ctx.stroke();

            ctx.fillStyle = '#ffffff';
            ctx.font = '900 122px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('STOP', 0, 8);
        } else if (signId === 's301_priority_next' || signId === 's102_rvl') {
            // Upward warning triangle
            ctx.beginPath();
            ctx.moveTo(0, -205);
            ctx.lineTo(215, 178);
            ctx.lineTo(-215, 178);
            ctx.closePath();
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.shadowColor = 'transparent';
            ctx.lineWidth = 50;
            ctx.strokeStyle = '#dc2626';
            ctx.lineJoin = 'round';
            ctx.stroke();
            ctx.lineWidth = 8;
            ctx.strokeStyle = '#0f172a';
            ctx.stroke();

            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 34;
            ctx.lineCap = 'round';
            if (signId === 's102_rvl') {
                ctx.beginPath();
                ctx.moveTo(-68, -5); ctx.lineTo(68, 115);
                ctx.moveTo(68, -5);  ctx.lineTo(-68, 115);
                ctx.stroke();
            } else {
                ctx.beginPath();
                ctx.moveTo(0, 125); ctx.lineTo(0, -55);
                ctx.moveTo(-55, 8); ctx.lineTo(0, -62); ctx.lineTo(55, 8);
                ctx.stroke();
                ctx.lineWidth = 20;
                ctx.beginPath();
                ctx.moveTo(-55, 48); ctx.lineTo(55, 48);
                ctx.stroke();
            }
        } else if (signId === 's350_crosswalk') {
            ctx.fillStyle = '#2563eb';
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 24;
            ctx.fillRect(-195, -195, 390, 390);
            ctx.strokeRect(-195, -195, 390, 390);
            ctx.shadowColor = 'transparent';
            ctx.lineWidth = 8;
            ctx.strokeStyle = '#0f172a';
            ctx.strokeRect(-205, -205, 410, 410);

            ctx.beginPath();
            ctx.moveTo(0, -145);
            ctx.lineTo(155, 132);
            ctx.lineTo(-155, 132);
            ctx.closePath();
            ctx.fillStyle = '#ffffff';
            ctx.fill();

            // Zebra stripes & walking person icon inside triangle
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 22;
            ctx.setLineDash([26, 18]);
            ctx.beginPath();
            ctx.moveTo(-95, 92);
            ctx.lineTo(95, 92);
            ctx.stroke();
            ctx.setLineDash([]);

            ctx.fillStyle = '#0f172a';
            ctx.beginPath();
            ctx.arc(0, -42, 22, 0, Math.PI * 2);
            ctx.fill();
            ctx.lineWidth = 20;
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.moveTo(0, -15); ctx.lineTo(0, 35);
            ctx.moveTo(0, 35);  ctx.lineTo(-28, 78);
            ctx.moveTo(0, 35);  ctx.lineTo(28, 78);
            ctx.moveTo(-32, 8); ctx.lineTo(32, -2);
            ctx.stroke();
        } else if (signId.startsWith('abk_') || signId === 'rad_cross') {
            // Rectangular Zusatzzeichen (Supplemental white board)
            ctx.fillStyle = '#ffffff';
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 22;
            ctx.fillRect(-205, -162, 410, 324);
            ctx.strokeRect(-205, -162, 410, 324);
            ctx.shadowColor = 'transparent';

            if (signId === 'rad_cross') {
                // Draw crisp vector bicycle + left/right arrows
                ctx.strokeStyle = '#0f172a';
                ctx.lineWidth = 16;
                ctx.lineCap = 'round';
                // Two wheels
                ctx.beginPath();
                ctx.arc(-65, 15, 36, 0, Math.PI * 2);
                ctx.stroke();
                ctx.beginPath();
                ctx.arc(65, 15, 36, 0, Math.PI * 2);
                ctx.stroke();
                // Frame
                ctx.beginPath();
                ctx.moveTo(-65, 15); ctx.lineTo(-10, -35); ctx.lineTo(50, -35); ctx.lineTo(65, 15);
                ctx.moveTo(-10, -35); ctx.lineTo(5, 15); ctx.lineTo(-65, 15);
                ctx.stroke();
                // Two-way horizontal arrow above
                ctx.lineWidth = 18;
                ctx.beginPath();
                ctx.moveTo(-120, -95); ctx.lineTo(120, -95);
                ctx.moveTo(-85, -120); ctx.lineTo(-122, -95); ctx.lineTo(-85, -70);
                ctx.moveTo(85, -120);  ctx.lineTo(122, -95);  ctx.lineTo(85, -70);
                ctx.stroke();
            } else {
                // Thin side roads
                ctx.strokeStyle = '#94a3b8';
                ctx.lineWidth = 26;
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(0, -118); ctx.lineTo(0, 118);
                ctx.moveTo(-138, 0); ctx.lineTo(138, 0);
                ctx.stroke();

                // Thick priority road curve (Abknickende Vorfahrt)
                ctx.strokeStyle = '#0f172a';
                ctx.lineWidth = 58;
                ctx.lineJoin = 'round';
                ctx.lineCap = 'round';
                ctx.beginPath();
                if (signId === 'abk_bottom_left') {
                    ctx.moveTo(0, 118); ctx.lineTo(0, 0); ctx.lineTo(-138, 0);
                } else if (signId === 'abk_bottom_right') {
                    ctx.moveTo(0, 118); ctx.lineTo(0, 0); ctx.lineTo(138, 0);
                } else if (signId === 'abk_top_left') {
                    ctx.moveTo(0, -118); ctx.lineTo(0, 0); ctx.lineTo(-138, 0);
                } else if (signId === 'abk_top_right') {
                    ctx.moveTo(0, -118); ctx.lineTo(0, 0); ctx.lineTo(138, 0);
                }
                ctx.stroke();
            }
        }

        ctx.restore();

        const tex = new THREE.CanvasTexture(canvas);
        tex.minFilter = THREE.LinearFilter;
        tex.magFilter = THREE.LinearFilter;
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
            const cargoMat = new THREE.MeshStandardMaterial({ color: 0x3b82f6, roughness: 0.4, metalness: 0.1 });
            const cargo = new THREE.Mesh(new THREE.BoxGeometry(2.35, 1.95, 3.4), cargoMat);
            cargo.position.set(0, 1.25, -0.75);
            cargo.castShadow = true;
            group.add(cargo);

            const cargoRoof = new THREE.Mesh(new THREE.BoxGeometry(2.2, 0.12, 3.25), roofMat);
            cargoRoof.position.set(0, 2.25, -0.75);
            group.add(cargoRoof);

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

            [-0.68, 0.68].forEach(hx => {
                const hl = new THREE.Mesh(new THREE.SphereGeometry(0.18, 10, 10), headlightMat);
                hl.position.set(hx, 0.72, 1.92);
                group.add(hl);

                const tl = new THREE.Mesh(new THREE.BoxGeometry(0.34, 0.18, 0.1), taillightMat);
                tl.position.set(hx, 0.72, -1.92);
                group.add(tl);
            });

            [[-1.02, -1.18], [1.02, -1.18], [-1.02, 1.18], [1.02, 1.18]].forEach(([wx, wz]) => {
                const w = new THREE.Mesh(wheelGeo, wheelMat);
                w.position.set(wx, 0.42, wz);
                group.add(w);
            });
        }

        // Blinking 3D Turn Signal
        if (direction === 'left' || direction === 'right') {
            const sigX = direction === 'left' ? 0.96 : -0.96;
            const sigZ = spec.kind === 'bus' ? 2.68 : (spec.kind === 'truck' ? 2.62 : 1.92);
            const blinkerMat = new THREE.MeshStandardMaterial({
                color: 0xf97316,
                emissive: 0xf59e0b,
                emissiveIntensity: 1.3
            });
            const blinker = new THREE.Mesh(new THREE.SphereGeometry(0.26, 12, 12), blinkerMat);
            blinker.position.set(sigX, 0.85, sigZ);
            blinker.userData.isBlinker = true;
            group.add(blinker);
        }

        return group;
    }

    /**
     * Build a Detailed, High-Visibility 3D Pedestrian / Cyclist / Scooter Mesh Group
     * Clearly recognizable from a 3/4 top-down perspective, with legs, shoes, torso,
     * backpack, arms, head, hair/cap, and a bright glowing ground locator ring!
     */
    function create3DPedestrianMesh(typeId) {
        const spec = PEDESTRIAN_SPECS[typeId] || PEDESTRIAN_SPECS.pedestrian;
        const group = new THREE.Group();

        const scale = spec.kind === 'child' ? 0.88 : 1.15;

        const shirtMat = new THREE.MeshStandardMaterial({ color: spec.color, roughness: 0.35 });
        const pantsMat = new THREE.MeshStandardMaterial({ color: spec.pants || 0x1e293b, roughness: 0.6 });
        const skinMat  = new THREE.MeshStandardMaterial({ color: 0xfdba74, roughness: 0.4 });
        const hairMat  = new THREE.MeshStandardMaterial({ color: spec.hair || 0x451a03, roughness: 0.5 });
        const shoeMat  = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.3 });
        const darkMat  = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.6 });

        // 1. High-Contrast Ground Highlight Base Disc & Ring (so pedestrian stands out immediately!)
        const baseDisc = new THREE.Mesh(
            new THREE.CylinderGeometry(1.15, 1.15, 0.08, 24),
            new THREE.MeshBasicMaterial({ color: 0x0f172a, transparent: true, opacity: 0.6 })
        );
        baseDisc.position.y = 0.05;
        group.add(baseDisc);

        const baseRing = new THREE.Mesh(
            new THREE.CylinderGeometry(1.3, 1.3, 0.06, 24),
            new THREE.MeshStandardMaterial({
                color: 0xfacc15,
                emissive: 0xfacc15,
                emissiveIntensity: 0.65
            })
        );
        baseRing.position.y = 0.03;
        group.add(baseRing);

        // 2. Character Body Group (animated bobbing during walk)
        const charBody = new THREE.Group();
        charBody.userData.isPedBody = true;
        group.add(charBody);

        // Legs in walking stride (+Z is forward)
        const legGeo = new THREE.CylinderGeometry(0.18 * scale, 0.16 * scale, 0.95 * scale, 12);
        const shoeGeo = new THREE.BoxGeometry(0.28 * scale, 0.18 * scale, 0.48 * scale);

        const leftLeg = new THREE.Group();
        const leftLegMesh = new THREE.Mesh(legGeo, pantsMat);
        leftLegMesh.position.y = 0.48 * scale;
        leftLegMesh.castShadow = true;
        const leftShoe = new THREE.Mesh(shoeGeo, shoeMat);
        leftShoe.position.set(0, 0.1 * scale, 0.12 * scale);
        leftLeg.add(leftLegMesh, leftShoe);
        leftLeg.position.set(-0.24 * scale, 0.05, 0.18 * scale);
        leftLeg.rotation.x = -0.22;
        charBody.add(leftLeg);

        const rightLeg = new THREE.Group();
        const rightLegMesh = new THREE.Mesh(legGeo, pantsMat);
        rightLegMesh.position.y = 0.48 * scale;
        rightLegMesh.castShadow = true;
        const rightShoe = new THREE.Mesh(shoeGeo, shoeMat);
        rightShoe.position.set(0, 0.1 * scale, 0.12 * scale);
        rightLeg.add(rightLegMesh, rightShoe);
        rightLeg.position.set(0.24 * scale, 0.05, -0.18 * scale);
        rightLeg.rotation.x = 0.22;
        charBody.add(rightLeg);

        // Torso / Jacket
        const torso = new THREE.Mesh(
            new THREE.BoxGeometry(0.92 * scale, 1.12 * scale, 0.56 * scale),
            shirtMat
        );
        torso.position.y = 1.42 * scale;
        torso.castShadow = true;
        charBody.add(torso);

        // High-visibility safety vest stripe / collar
        const stripe = new THREE.Mesh(
            new THREE.BoxGeometry(0.95 * scale, 0.18 * scale, 0.59 * scale),
            new THREE.MeshStandardMaterial({ color: 0xfef08a, emissive: 0xfacc15, emissiveIntensity: 0.35 })
        );
        stripe.position.y = 1.48 * scale;
        charBody.add(stripe);

        // Backpack on back (-Z) so front/back orientation is unmistakable
        const pack = new THREE.Mesh(
            new THREE.BoxGeometry(0.62 * scale, 0.68 * scale, 0.32 * scale),
            new THREE.MeshStandardMaterial({ color: 0xf59e0b, roughness: 0.4 })
        );
        pack.position.set(0, 1.46 * scale, -0.38 * scale);
        pack.castShadow = true;
        charBody.add(pack);

        // Left & Right Arms + Hands
        const armGeo = new THREE.CylinderGeometry(0.14 * scale, 0.13 * scale, 0.85 * scale, 10);
        const handGeo = new THREE.SphereGeometry(0.16 * scale, 10, 10);

        [-1, 1].forEach(side => {
            const armGroup = new THREE.Group();
            const armMesh = new THREE.Mesh(armGeo, shirtMat);
            armMesh.position.y = -0.32 * scale;
            armMesh.castShadow = true;
            const handMesh = new THREE.Mesh(handGeo, skinMat);
            handMesh.position.y = -0.76 * scale;
            armGroup.add(armMesh, handMesh);
            armGroup.position.set(side * 0.6 * scale, 1.82 * scale, 0);
            armGroup.rotation.x = side * 0.28;
            armGroup.rotation.z = side * -0.12;
            charBody.add(armGroup);
        });

        // Head + Hair/Cap + Eyes facing +Z
        const head = new THREE.Mesh(new THREE.SphereGeometry(0.46 * scale, 18, 18), skinMat);
        head.position.y = 2.38 * scale;
        head.castShadow = true;
        charBody.add(head);

        // Cap / Hair on top + visor pointing forward (+Z)
        const capCrown = new THREE.Mesh(
            new THREE.SphereGeometry(0.48 * scale, 16, 12, 0, Math.PI * 2, 0, Math.PI * 0.58),
            hairMat
        );
        capCrown.position.y = 2.42 * scale;
        charBody.add(capCrown);

        const capVisor = new THREE.Mesh(
            new THREE.BoxGeometry(0.56 * scale, 0.1 * scale, 0.42 * scale),
            hairMat
        );
        capVisor.position.set(0, 2.52 * scale, 0.36 * scale);
        charBody.add(capVisor);

        // Eyes on front face (+Z)
        [-0.15, 0.15].forEach(ex => {
            const eye = new THREE.Mesh(new THREE.SphereGeometry(0.065 * scale, 8, 8), darkMat);
            eye.position.set(ex * scale, 2.38 * scale, 0.42 * scale);
            charBody.add(eye);
        });

        // 3. If Cyclist or Scooter on crosswalk, add 3D Bicycle / Scooter frame & wheels
        if (spec.kind === 'cyclist' || spec.kind === 'scooter') {
            const wheelGeo = new THREE.CylinderGeometry(0.42, 0.42, 0.16, 16);
            wheelGeo.rotateZ(Math.PI / 2);
            [-0.75, 0.75].forEach(wz => {
                const w = new THREE.Mesh(wheelGeo, darkMat);
                w.position.set(0, 0.42, wz);
                group.add(w);
            });
            const deck = new THREE.Mesh(
                new THREE.BoxGeometry(0.32, 0.18, 1.65),
                new THREE.MeshStandardMaterial({ color: 0xfacc15, roughness: 0.3 })
            );
            deck.position.set(0, 0.38, 0);
            group.add(deck);

            const stem = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 1.35, 8), darkMat);
            stem.position.set(0, 1.05, 0.68);
            const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 0.95, 8), darkMat);
            bar.rotation.z = Math.PI / 2;
            bar.position.set(0, 1.68, 0.68);
            group.add(stem, bar);
        }

        // 4. Invisible larger hit cylinder so clicking/tapping the pedestrian is super easy
        const hitBox = new THREE.Mesh(
            new THREE.CylinderGeometry(1.45, 1.45, 3.6, 12),
            new THREE.MeshBasicMaterial({ visible: false })
        );
        hitBox.position.y = 1.8;
        group.add(hitBox);

        return group;
    }

    /**
     * Build Large, High-Visibility 3D Signpost (Single or Stacked Dual Signs) & 3D Traffic Light.
     * The displayHead is automatically billboarded toward the camera in animateLoop() so
     * signs and lights are ALWAYS 100% readable, large, and never viewed edge-on or backwards!
     */
    function create3DSignpostAndLightGroup(branchData) {
        const group = new THREE.Group();
        const hasPrimary = branchData.primarySign && branchData.primarySign !== 'none';
        const hasSecondary = branchData.secondarySign && branchData.secondarySign !== 'none';
        const hasLight = branchData.trafficLight && branchData.trafficLight !== 'none';

        if (!hasPrimary && !hasSecondary && !hasLight) return group;

        const isStacked = hasPrimary && hasSecondary;
        const poleHeight = 3.8;

        // Thicker 3D metallic pole supporting the sign head from below
        const poleMat = new THREE.MeshStandardMaterial({ color: 0xcbd5e1, metalness: 0.55, roughness: 0.3 });
        const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.24, poleHeight, 14), poleMat);
        pole.position.y = poleHeight / 2;
        pole.castShadow = true;
        group.add(pole);

        const base = new THREE.Mesh(
            new THREE.CylinderGeometry(0.72, 0.88, 0.34, 18),
            new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.6 })
        );
        base.position.y = 0.17;
        group.add(base);

        // Billboarded Display Head (faces camera screen plane directly so signs are huge & unmistakable!)
        const displayHead = new THREE.Group();
        displayHead.position.y = isStacked ? 4.9 : 4.2;
        displayHead.userData.isBillboardSignHead = true;
        group.add(displayHead);

        // Primary Sign (Large & Crisp: 3.5 x 3.5 units)
        if (hasPrimary) {
            const tex1 = getSignCanvasTexture(branchData.primarySign);
            const signY = hasSecondary ? 1.3 : 0.2;

            const signPlane1 = new THREE.Mesh(
                new THREE.PlaneGeometry(3.5, 3.5),
                new THREE.MeshBasicMaterial({ map: tex1, transparent: true, side: THREE.DoubleSide })
            );
            signPlane1.position.set(0, signY, 1.05);
            displayHead.add(signPlane1);
        }

        // Secondary Stacked Sign Below Primary Sign (3.0 x 2.35 units)
        if (hasSecondary) {
            const tex2 = getSignCanvasTexture(branchData.secondarySign);
            const signY2 = hasPrimary ? -1.55 : 0.2;

            const signPlane2 = new THREE.Mesh(
                new THREE.PlaneGeometry(3.0, 2.35),
                new THREE.MeshBasicMaterial({ map: tex2, transparent: true, side: THREE.DoubleSide })
            );
            signPlane2.position.set(0, signY2, 1.05);
            displayHead.add(signPlane2);
        }

        // Large 3D Traffic Light next to or on the post
        if (hasLight) {
            const lightOffsetX = (hasPrimary || hasSecondary) ? -2.45 : 0;
            const boxGroup = new THREE.Group();
            boxGroup.position.set(lightOffsetX, 0.2, 1.05);

            // Outer yellow/dark casing for high contrast
            const borderBox = new THREE.Mesh(
                new THREE.BoxGeometry(1.5, 3.45, 0.52),
                new THREE.MeshStandardMaterial({ color: 0xfacc15, roughness: 0.4 })
            );
            boxGroup.add(borderBox);

            const housing = new THREE.Mesh(
                new THREE.BoxGeometry(1.3, 3.25, 0.6),
                new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.5 })
            );
            boxGroup.add(housing);

            const st = branchData.trafficLight;
            const lamps = [
                { y: 0.96,  active: st === 'red',    onCol: 0xef4444, offCol: 0x450a0a },
                { y: 0.0,   active: st === 'yellow', onCol: 0xfacc15, offCol: 0x422006 },
                { y: -0.96, active: st === 'green',  onCol: 0x22c55e, offCol: 0x052e16 }
            ];
            lamps.forEach(l => {
                const mat = new THREE.MeshStandardMaterial({
                    color: l.active ? l.onCol : l.offCol,
                    emissive: l.active ? l.onCol : 0x000000,
                    emissiveIntensity: l.active ? 1.8 : 0
                });
                const bulb = new THREE.Mesh(new THREE.SphereGeometry(0.42, 18, 18), mat);
                bulb.position.set(0, l.y, 0.26);
                boxGroup.add(bulb);
            });

            displayHead.add(boxGroup);
        }

        return group;
    }

    /**
     * Determine which branch a vehicle exits into based on its starting branch and turn direction
     */
    function getVehicleTargetBranch(branch, direction) {
        const dir = direction || 'straight';
        const map = {
            bottom: { straight: 'top',    right: 'right',  left: 'left' },
            top:    { straight: 'bottom', right: 'left',   left: 'right' },
            right:  { straight: 'left',   right: 'top',    left: 'bottom' },
            left:   { straight: 'right',  right: 'bottom', left: 'top' }
        };
        return (map[branch] && map[branch][dir]) ? map[branch][dir] : 'top';
    }

    /**
     * Build 3D Curve (THREE.CurvePath / LineCurve3) for an Actor's Trajectory.
     * Turning vehicles follow a 3-part path (approach -> smooth intersection turn -> straight outgoing lane)
     * so all vehicles entering the same target branch align collinearly in the lane and queue behind one another!
     */
    function buildActor3DCurve(actor, exitDist = 14.5) {
        const b = actor.branch;
        if (actor.slotType === 'pedestrian') {
            const g = BRANCH_3D[b];
            return new THREE.LineCurve3(
                new THREE.Vector3(g.pedStart.x, 0.28, g.pedStart.z),
                new THREE.Vector3(g.pedEnd.x, 0.28, g.pedEnd.z)
            );
        }

        const start = BRANCH_3D[b].vehicleStart;
        const dir = actor.direction || 'straight';
        const y = 0.28;
        const p0 = new THREE.Vector3(start.x, y, start.z);
        const targetBranch = getVehicleTargetBranch(b, dir);

        const exitEndMap = {
            top:    new THREE.Vector3(2.4, y, -exitDist),
            bottom: new THREE.Vector3(-2.4, y, exitDist),
            right:  new THREE.Vector3(exitDist, y, 2.4),
            left:   new THREE.Vector3(-exitDist, y, -2.4)
        };
        const exitEndPt = exitEndMap[targetBranch] || new THREE.Vector3(0, y, 0);

        if (dir === 'straight') {
            return new THREE.LineCurve3(p0, exitEndPt);
        }

        const entryMap = {
            bottom: new THREE.Vector3(2.4, y, 4.8),
            top:    new THREE.Vector3(-2.4, y, -4.8),
            right:  new THREE.Vector3(4.8, y, -2.4),
            left:   new THREE.Vector3(-4.8, y, 2.4)
        };
        const exitStartMap = {
            top:    new THREE.Vector3(2.4, y, -4.8),
            bottom: new THREE.Vector3(-2.4, y, 4.8),
            right:  new THREE.Vector3(4.8, y, 2.4),
            left:   new THREE.Vector3(-4.8, y, -2.4)
        };

        const entryPt = entryMap[b];
        const exitStartPt = exitStartMap[targetBranch];
        const ctrlPt = (b === 'bottom' || b === 'top')
            ? new THREE.Vector3(entryPt.x, y, exitStartPt.z)
            : new THREE.Vector3(exitStartPt.x, y, entryPt.z);

        const path = new THREE.CurvePath();
        path.add(new THREE.LineCurve3(p0, entryPt));
        path.add(new THREE.QuadraticBezierCurve3(entryPt, ctrlPt, exitStartPt));
        path.add(new THREE.LineCurve3(exitStartPt, exitEndPt));
        return path;
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

        const segments = 18;
        for (let i = 1; i < segments - 1; i += 2) {
            const t1 = i / segments;
            const t2 = (i + 1) / segments;
            const pt1 = curve.getPointAt(t1);
            const pt2 = curve.getPointAt(t2);
            const segCurve = new THREE.LineCurve3(pt1, pt2);
            const tubeGeo = new THREE.TubeGeometry(segCurve, 4, 0.24, 8, false);
            const mesh = new THREE.Mesh(tubeGeo, mat);
            group.add(mesh);
        }

        const tipPt = curve.getPointAt(0.92);
        const tangent = curve.getTangentAt(0.92).normalize();
        const coneGeo = new THREE.ConeGeometry(0.68, 1.55, 14);
        coneGeo.rotateX(Math.PI / 2);
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
            this.actorsMap = {};
            this.billboardSignHeads = [];
            this.clickedKeys = [];
            this.moveOrder = [];
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

            // Perspective Camera — Default view set to Driver View ('driverSouth') and slightly zoomed out
            this.camera = new THREE.PerspectiveCamera(38, w / h, 1, 220);

            this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
            this.renderer.setSize(w, h);
            this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
            this.renderer.shadowMap.enabled = true;
            this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            this.container.innerHTML = '';
            this.container.appendChild(this.renderer.domElement);

            // Balanced Lights so road & sidewalk colors stay rich and never wash out to white
            const hemi = new THREE.HemisphereLight(0xffffff, 0x334155, 0.65);
            hemi.position.set(0, 50, 0);
            this.scene.add(hemi);

            const dirLight = new THREE.DirectionalLight(0xfffbeb, 0.78);
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

            this.isDraggingCam = false;
            this.dragStart = { x: 0, y: 0 };
            this.pointerMoved = false;

            // Default camera view: Driver View ('driverSouth') at comfortable zoomed-out distance
            this.setCameraView('driverSouth');

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
            if (preset === 'driverSouth') {
                this.camAzimuth = 0;
                this.camElevation = 0.68;
                this.camDist = 52;
            } else if (preset === 'iso34') {
                this.camAzimuth = 0.42;
                this.camElevation = 0.88;
                this.camDist = 52;
            } else if (preset === 'isoLeft') {
                this.camAzimuth = -0.45;
                this.camElevation = 0.86;
                this.camDist = 52;
            } else if (preset === 'topDown') {
                this.camAzimuth = 0;
                this.camElevation = 1.45;
                this.camDist = 54;
            }
            if (this.camera) this.updateCameraFromSpherical();
            if (this.camBarEl) {
                this.camBarEl.querySelectorAll('button').forEach(btn => {
                    const isActive = btn.getAttribute('data-cam') === preset;
                    btn.className = `btn btn-sm btn-dark border ${isActive ? 'border-warning text-warning' : 'border-light text-white'} fw-bold py-1 px-2`;
                });
            }
        }

        initOverlays() {
            this.overlayLayer = document.createElement('div');
            this.overlayLayer.style.cssText = 'position:absolute;inset:0;pointer-events:none;overflow:hidden;z-index:5;';
            this.container.appendChild(this.overlayLayer);

            const camBar = document.createElement('div');
            this.camBarEl = camBar;
            camBar.style.cssText = 'position:absolute;top:10px;left:10px;z-index:10;display:flex;gap:5px;flex-wrap:wrap;';
            camBar.innerHTML = `
                <button type="button" class="btn btn-sm btn-dark border border-warning text-warning fw-bold py-1 px-2" style="font-size:0.72rem;opacity:0.92;" data-cam="driverSouth">نمای راننده</button>
                <button type="button" class="btn btn-sm btn-dark border border-light text-white fw-bold py-1 px-2" style="font-size:0.72rem;opacity:0.85;" data-cam="iso34">نمای سه‌رخ ۳ بعدی</button>
                <button type="button" class="btn btn-sm btn-dark border border-light text-white fw-bold py-1 px-2" style="font-size:0.72rem;opacity:0.85;" data-cam="topDown">نمای بالا</button>
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

            while (this.worldGroup.children.length > 0) {
                this.worldGroup.remove(this.worldGroup.children[0]);
            }
            this.overlayLayer.innerHTML = '';
            this.actorsMap = {};
            this.billboardSignHeads = [];
            this.activeDropZoneEls = [];
            this.badgeOverlayEls = {};

            const interType = (scenarioState && scenarioState.intersectionType) || 'cross4';
            const activeBranches = INTERSECTION_BRANCHES[interType] || INTERSECTION_BRANCHES.cross4;

            // 1. 3D Grass Ground (Top at Y = 0.0)
            const grassMat = new THREE.MeshStandardMaterial({ color: 0x22c55e, roughness: 0.92 });
            const grass = new THREE.Mesh(new THREE.BoxGeometry(64, 0.6, 64), grassMat);
            grass.position.y = -0.3;
            grass.receiveShadow = true;
            this.worldGroup.add(grass);

            // 2. Decorative 3D Low-Poly Trees in the outer corners
            [[-16, -16], [16, -16], [-16, 16], [16, 16], [-19, -11], [19, -11], [-19, 11], [19, 11]].forEach(([tx, tz]) => {
                const tree = new THREE.Group();
                const trunk = new THREE.Mesh(new THREE.CylinderGeometry(0.35, 0.45, 1.8, 8), new THREE.MeshStandardMaterial({ color: 0x78350f }));
                trunk.position.y = 0.9;
                trunk.castShadow = true;
                const crown1 = new THREE.Mesh(new THREE.ConeGeometry(1.75, 2.8, 10), new THREE.MeshStandardMaterial({ color: 0x15803d, roughness: 0.75 }));
                crown1.position.y = 2.6;
                crown1.castShadow = true;
                const crown2 = new THREE.Mesh(new THREE.ConeGeometry(1.35, 2.2, 10), new THREE.MeshStandardMaterial({ color: 0x16a34a, roughness: 0.75 }));
                crown2.position.y = 3.9;
                crown2.castShadow = true;
                tree.add(trunk, crown1, crown2);
                tree.position.set(tx, 0, tz);
                this.worldGroup.add(tree);
            });

            const hasSidewalks = !scenarioState || scenarioState.hasSidewalks !== false;
            const hasRoadMarkings = !scenarioState || scenarioState.hasRoadMarkings !== false;

            // 3. Properly Layered Sidewalks (پیاده‌رو: Y_top = 0.17) & Dark Asphalt Roads (خیابان: Y_top = 0.22)
            const sidewalkMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, roughness: 0.8 });
            const curbBorderMat = new THREE.MeshStandardMaterial({ color: 0x475569, roughness: 0.7 });
            const roadMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.88 });
            const lineMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
            const tactileMat = new THREE.MeshStandardMaterial({ color: 0xfacc15, roughness: 0.5 });

            if (hasSidewalks) {
                // Center sidewalk base (Y_top = 0.14 for border, 0.17 for concrete sidewalk)
                const centerCurbBase = new THREE.Mesh(new THREE.BoxGeometry(17.2, 0.14, 17.2), curbBorderMat);
                centerCurbBase.position.y = 0.07;
                centerCurbBase.receiveShadow = true;
                this.worldGroup.add(centerCurbBase);

                const centerSidewalk = new THREE.Mesh(new THREE.BoxGeometry(16.6, 0.16, 16.6), sidewalkMat);
                centerSidewalk.position.y = 0.09;
                centerSidewalk.receiveShadow = true;
                this.worldGroup.add(centerSidewalk);
            }

            // Center Dark Asphalt Road (Y_top = 0.22 — sits cleanly ABOVE sidewalk base so road is dark!)
            const centerAsphalt = new THREE.Mesh(new THREE.BoxGeometry(9.6, 0.22, 9.6), roadMat);
            centerAsphalt.position.y = 0.11;
            centerAsphalt.receiveShadow = true;
            this.worldGroup.add(centerAsphalt);

            const branchConfigs = {
                top:    { sx: 0, sz: -13.5, sw: 16.6, sd: 17.5, rw: 9.6, rd: 18.2, isVert: true },
                bottom: { sx: 0, sz: 13.5,  sw: 16.6, sd: 17.5, rw: 9.6, rd: 18.2, isVert: true },
                right:  { sx: 13.5, sz: 0,  sw: 17.5, sd: 16.6, rw: 18.2, rd: 9.6, isVert: false },
                left:   { sx: -13.5, sz: 0, sw: 17.5, sd: 16.6, rw: 18.2, rd: 9.6, isVert: false }
            };

            activeBranches.forEach(bName => {
                const cfg = branchConfigs[bName];

                if (hasSidewalks) {
                    // Outer curbstone rim for this branch's sidewalk (Y_top = 0.14)
                    const curbRim = new THREE.Mesh(
                        new THREE.BoxGeometry(cfg.isVert ? cfg.sw + 0.6 : cfg.sw, 0.14, cfg.isVert ? cfg.sd : cfg.sd + 0.6),
                        curbBorderMat
                    );
                    curbRim.position.set(cfg.sx, 0.07, cfg.sz);
                    curbRim.receiveShadow = true;
                    this.worldGroup.add(curbRim);

                    // Concrete sidewalk (پیاده‌رو: Y_top = 0.17)
                    const sw = new THREE.Mesh(new THREE.BoxGeometry(cfg.sw, 0.16, cfg.sd), sidewalkMat);
                    sw.position.set(cfg.sx, 0.09, cfg.sz);
                    sw.receiveShadow = true;
                    this.worldGroup.add(sw);

                    // Sidewalk paving tile lines on the outer walkways (Y_top = 0.18)
                    for (let step = 6.5; step <= 20.5; step += 2.4) {
                        const dirSign = (bName === 'top' || bName === 'left') ? -1 : 1;
                        [-6.6, 6.6].forEach(sideOffset => {
                            const seam = new THREE.Mesh(
                                new THREE.BoxGeometry(cfg.isVert ? 3.2 : 0.08, 0.16, cfg.isVert ? 0.08 : 3.2),
                                curbBorderMat
                            );
                            if (cfg.isVert) {
                                seam.position.set(sideOffset, 0.10, dirSign * step);
                            } else {
                                seam.position.set(dirSign * step, 0.10, sideOffset);
                            }
                            this.worldGroup.add(seam);
                        });
                    }
                }

                // Dark Road Asphalt (Y_top = 0.22 — sits cleanly above sidewalk base!)
                const rd = new THREE.Mesh(new THREE.BoxGeometry(cfg.rw, 0.22, cfg.rd), roadMat);
                rd.position.set(cfg.sx, 0.11, cfg.sz);
                rd.receiveShadow = true;
                this.worldGroup.add(rd);

                if (hasRoadMarkings) {
                    // Dashed center lane markings (Y_top = 0.25 — crisp white on dark asphalt!)
                    for (let d = 8.8; d <= 20.2; d += 3.0) {
                        const sign = (bName === 'top' || bName === 'left') ? -1 : 1;
                        const dash = new THREE.Mesh(
                            new THREE.BoxGeometry(cfg.isVert ? 0.24 : 1.65, 0.22, cfg.isVert ? 1.65 : 0.24),
                            lineMat
                        );
                        dash.position.set(cfg.isVert ? 0 : sign * d, 0.14, cfg.isVert ? sign * d : 0);
                        this.worldGroup.add(dash);
                    }
                }

                // Crosswalks (گذرگاه عابر پیاده: Y_top = 0.25) & Tactile Curb Ramps (Y_top = 0.20)
                const bData = scenarioState.branches ? scenarioState.branches[bName] : null;
                const hasCrosswalkOrPed = bData && (bData.hasCrosswalk || (bData.pedestrian && bData.pedestrian.type));

                if (hasCrosswalkOrPed) {
                    for (let offset = -3.85; offset <= 3.85; offset += 1.1) {
                        const stripe = new THREE.Mesh(
                            new THREE.BoxGeometry(cfg.isVert ? 0.62 : 2.35, 0.22, cfg.isVert ? 2.35 : 0.62),
                            lineMat
                        );
                        if (bName === 'bottom') stripe.position.set(offset, 0.14, 6.8);
                        if (bName === 'top')    stripe.position.set(offset, 0.14, -6.8);
                        if (bName === 'right')  stripe.position.set(6.8, 0.14, offset);
                        if (bName === 'left')   stripe.position.set(-6.8, 0.14, offset);
                        this.worldGroup.add(stripe);
                    }

                    if (hasSidewalks) {
                        [-5.35, 5.35].forEach(sidePos => {
                            const ramp = new THREE.Mesh(
                                new THREE.BoxGeometry(cfg.isVert ? 0.75 : 2.4, 0.18, cfg.isVert ? 2.4 : 0.75),
                                tactileMat
                            );
                            if (bName === 'bottom') ramp.position.set(sidePos, 0.11, 6.8);
                            if (bName === 'top')    ramp.position.set(sidePos, 0.11, -6.8);
                            if (bName === 'right')  ramp.position.set(6.8, 0.11, sidePos);
                            if (bName === 'left')   ramp.position.set(-6.8, 0.11, sidePos);
                            this.worldGroup.add(ramp);
                        });
                    }
                }

                if (hasRoadMarkings && bData && (bData.primarySign === 's206_stop' || bData.primarySign === 's205_yield')) {
                    const stopLine = new THREE.Mesh(
                        new THREE.BoxGeometry(cfg.isVert ? 4.4 : 0.48, 0.22, cfg.isVert ? 0.48 : 4.4),
                        lineMat
                    );
                    if (bName === 'bottom') stopLine.position.set(2.4, 0.14, 8.5);
                    if (bName === 'top')    stopLine.position.set(-2.4, 0.14, -8.5);
                    if (bName === 'right')  stopLine.position.set(8.5, 0.14, -2.4);
                    if (bName === 'left')   stopLine.position.set(-8.5, 0.14, 2.4);
                    this.worldGroup.add(stopLine);
                }

                // 4. Large 3D Traffic Signs & Traffic Lights on Branch Sidewalk Corner (or Roadside Grass)
                if (bData) {
                    const signGroup = create3DSignpostAndLightGroup(bData);
                    const sPos = BRANCH_3D[bName].signSlot;
                    if (hasSidewalks) {
                        signGroup.position.set(sPos.x, 0.18, sPos.z);
                    } else {
                        signGroup.position.set(sPos.x * 0.78, 0.02, sPos.z * 0.94);
                    }
                    this.worldGroup.add(signGroup);

                    signGroup.traverse(child => {
                        if (child.userData && child.userData.isBillboardSignHead) {
                            this.billboardSignHeads.push(child);
                        }
                    });
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
                            background:rgba(15,23,42,0.78);color:#fde047;
                            border:2px dashed rgba(250,204,21,0.9);border-radius:10px;
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

        /**
         * Calculate stopping distance in the outgoing lane so multiple vehicles entering
         * the same target branch park cleanly one behind another instead of overlapping!
         */
        getVehicleExitDist(actor) {
            if (!actor || actor.slotType === 'pedestrian') return 14.5;
            const targetBranch = getVehicleTargetBranch(actor.branch, actor.direction);
            const sequence = (this.moveOrder && this.moveOrder.length > 0)
                ? this.moveOrder
                : (this.clickedKeys || []);

            let queueIndex = 0;
            for (let i = 0; i < sequence.length; i++) {
                const k = sequence[i];
                if (k === actor.key) break;
                if (k.indexOf('vehicle_') === 0) {
                    const otherBranch = k.replace('vehicle_', '');
                    const bData = (this.scenarioState && this.scenarioState.branches)
                        ? this.scenarioState.branches[otherBranch]
                        : null;
                    if (bData && bData.vehicle && bData.vehicle.type) {
                        const otherTarget = getVehicleTargetBranch(otherBranch, bData.vehicle.direction || 'straight');
                        if (otherTarget === targetBranch) {
                            queueIndex++;
                        }
                    }
                }
            }

            // 1st vehicle pulls forward to 17.4, 2nd stops behind it at 11.4, 3rd stops behind 2nd at 5.6
            const exitSlots = [17.4, 11.4, 5.6];
            return exitSlots[Math.min(queueIndex, exitSlots.length - 1)];
        }

        spawnActor3D(actor) {
            const arrowCurve = buildActor3DCurve(actor, 14.5);

            let trajHex = 0xfacc15;
            if (this.evaluationResult && this.evaluationResult.detailsByKey) {
                const ok = this.evaluationResult.detailsByKey[actor.key];
                trajHex = ok ? 0x22c55e : 0xef4444;
            }

            const trajMesh = create3DTrajectoryArrowMesh(arrowCurve, trajHex);
            this.worldGroup.add(trajMesh);

            const isPed = actor.slotType === 'pedestrian';
            const mesh = isPed
                ? create3DPedestrianMesh(actor.type)
                : create3DVehicleMesh(actor.type, actor.direction);

            const startSpec = isPed
                ? BRANCH_3D[actor.branch].pedStart
                : BRANCH_3D[actor.branch].vehicleStart;

            const baseY = 0.22;
            const exitDist = isPed ? 14.5 : this.getVehicleExitDist(actor);
            const moveCurve = isPed ? arrowCurve : buildActor3DCurve(actor, exitDist);
            const uMax = isPed ? 0.88 : 0.999;

            if (this.completedMoves && this.completedMoves.has(actor.key)) {
                const pt = moveCurve.getPointAt(uMax);
                const tangent = moveCurve.getTangentAt(uMax).normalize();
                mesh.position.set(pt.x, baseY, pt.z);
                mesh.lookAt(new THREE.Vector3(pt.x + tangent.x, baseY, pt.z + tangent.z));
            } else {
                mesh.position.set(startSpec.x, baseY, startSpec.z);
                mesh.rotation.y = startSpec.rotY;
            }
            mesh.userData.actorKey = actor.key;
            mesh.userData.branch = actor.branch;
            this.worldGroup.add(mesh);

            // Floating HTML Priority / Click Order Badge anchored above the 3D actor
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
                min-width:30px;height:30px;padding:0 8px;
                display:inline-flex;align-items:center;justify-content:center;
                font-weight:900;font-size:0.84rem;box-shadow:0 3px 0 #020617;
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
                curve: moveCurve,
                uMax: uMax,
                badgeEl: badgeEl,
                startSpec: startSpec,
                baseY: baseY,
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

            if (!this.moveOrder) this.moveOrder = [];
            if (!this.moveOrder.includes(actorKey)) {
                this.moveOrder.push(actorKey);
            }

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

            if (entry.actor.slotType !== 'pedestrian') {
                const exitDist = this.getVehicleExitDist(entry.actor);
                entry.curve = buildActor3DCurve(entry.actor, exitDist);
                entry.uMax = 0.999;
            }

            entry.animStartTime = performance.now();
            entry.isAnimating = true;
        }

        resetPositions() {
            if (this.simTimers && this.simTimers.length) {
                this.simTimers.forEach(t => clearTimeout(t));
            }
            this.simTimers = [];
            this.completedMoves = new Set();
            this.moveOrder = [];
            this.clickedKeys = [];
            this.evaluationResult = null;
            Object.values(this.actorsMap).forEach(entry => {
                entry.isAnimating = false;
                if (entry.mesh && entry.startSpec) {
                    entry.mesh.position.set(entry.startSpec.x, entry.baseY || 0.2, entry.startSpec.z);
                    entry.mesh.rotation.set(0, entry.startSpec.rotY, 0);
                }
                if (entry.badgeEl) {
                    entry.badgeEl.style.background = '#0f172a';
                    entry.badgeEl.style.borderColor = '#fde047';
                    entry.badgeEl.textContent = this.showDropZones ? entry.actor.priority : '؟';
                }
            });
        }

        /**
         * Animate the correct priority order where vehicles sharing the same priority number
         * (e.g. Priority 1: Blue Truck & Red Car) move SIMULTANEOUSLY at the exact same moment!
         */
        playSimultaneousSolution() {
            if (this.simTimers && this.simTimers.length) {
                this.simTimers.forEach(t => clearTimeout(t));
            }
            this.simTimers = [];
            this.completedMoves = new Set();
            this.moveOrder = [];

            const entries = Object.values(this.actorsMap);
            entries.forEach(entry => {
                entry.isAnimating = false;
                if (entry.mesh && entry.startSpec) {
                    entry.mesh.position.set(entry.startSpec.x, entry.baseY || 0.2, entry.startSpec.z);
                    entry.mesh.rotation.set(0, entry.startSpec.rotY, 0);
                }
            });

            const uniquePriorities = [...new Set(entries.map(e => e.actor.priority))].sort((a, b) => a - b);

            uniquePriorities.forEach((prio, tierIdx) => {
                const timer = setTimeout(() => {
                    const groupEntries = entries.filter(e => e.actor.priority === prio);
                    groupEntries.forEach(entry => {
                        const key = entry.actor.key;
                        this.completedMoves.add(key);
                        if (!this.moveOrder.includes(key)) {
                            this.moveOrder.push(key);
                        }
                        if (entry.actor.slotType !== 'pedestrian') {
                            const exitDist = this.getVehicleExitDist(entry.actor);
                            entry.curve = buildActor3DCurve(entry.actor, exitDist);
                            entry.uMax = 0.999;
                        }
                        entry.animStartTime = performance.now();
                        entry.isAnimating = true;
                    });
                }, tierIdx * 1200);
                this.simTimers.push(timer);
            });
        }

        updateOverlayProjections() {
            const w = this.renderer.domElement.clientWidth;
            const h = this.renderer.domElement.clientHeight;
            if (!w || !h) return;

            Object.values(this.actorsMap).forEach(entry => {
                if (!entry.mesh || !entry.badgeEl) return;
                const pos = entry.mesh.position.clone();
                pos.y += entry.actor.slotType === 'pedestrian' ? 3.35 : 2.45;
                pos.project(this.camera);
                const x = (pos.x * 0.5 + 0.5) * w;
                const y = (-(pos.y * 0.5) + 0.5) * h;
                entry.badgeEl.style.left = `${x}px`;
                entry.badgeEl.style.top = `${y}px`;
            });

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

            // Keep all 3D traffic signs & traffic lights facing the camera screen plane directly (billboarded)
            // so they are always huge, upright, and 100% legible from every angle!
            if (this.camera && this.billboardSignHeads && this.billboardSignHeads.length > 0) {
                this.billboardSignHeads.forEach(head => {
                    head.quaternion.copy(this.camera.quaternion);
                });
            }

            // Update vehicle turn signals, pedestrian walking bob, & active curve movements
            Object.values(this.actorsMap).forEach(entry => {
                if (entry.mesh) {
                    entry.mesh.traverse(child => {
                        if (child.userData && child.userData.isBlinker) {
                            child.visible = blinkOn;
                        }
                        if (child.userData && child.userData.isPedBody) {
                            if (entry.isAnimating) {
                                child.position.y = Math.abs(Math.sin(now * 0.016)) * 0.28;
                                child.rotation.z = Math.sin(now * 0.016) * 0.1;
                            } else {
                                child.position.y = Math.abs(Math.sin(now * 0.004)) * 0.06;
                                child.rotation.z = 0;
                            }
                        }
                    });
                }

                if (entry.isAnimating && entry.curve) {
                    const duration = 1150;
                    const t = Math.min((now - entry.animStartTime) / duration, 1);
                    const eased = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;

                    const uMax = entry.uMax || 0.88;
                    const u = eased * uMax;
                    const pt = entry.curve.getPointAt(u);
                    const tangent = entry.curve.getTangentAt(u).normalize();
                    const yLevel = entry.baseY || 0.2;

                    entry.mesh.position.set(pt.x, yLevel, pt.z);
                    const targetLook = new THREE.Vector3(pt.x + tangent.x, yLevel, pt.z + tangent.z);
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
