/*
 * Global resort stylesheet — imported by AssetMapper.
 * The full CSS is in assets/styles/app.css (copied from the legacy style.css).
 */
import './styles/app.css';

// ─── Resort JS (ported from legacy script.js) ───────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    // Intersection Observer for scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -40px 0px'
    });

    document.querySelectorAll('.animate-in').forEach(el => observer.observe(el));

    // Set minimum check-in date to today
    const today = new Date().toISOString().split('T')[0];
    const checkinInput = document.getElementById('checkin');
    const checkoutInput = document.getElementById('checkout');

    if (checkinInput) checkinInput.setAttribute('min', today);

    checkinInput?.addEventListener('change', () => {
        checkoutInput.setAttribute('min', checkinInput.value);
        if (checkoutInput.value && checkoutInput.value < checkinInput.value) {
            checkoutInput.value = checkinInput.value;
        }
    });

    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    const navLinks = document.querySelector('.navbar-links');
    const mobileMenuBackdrop = document.getElementById('mobileMenuBackdrop');

    const setMobileMenuState = (isOpen) => {
        navLinks?.classList.toggle('open', isOpen);
        mobileMenuBackdrop?.classList.toggle('open', isOpen);
        menuToggle?.classList.toggle('open', isOpen);
        menuToggle?.setAttribute('aria-expanded', String(isOpen));
        menuToggle?.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        document.body.classList.toggle('mobile-menu-open', isOpen);
    };

    menuToggle?.addEventListener('click', () => {
        const isOpen = navLinks?.classList.toggle('open') ?? false;
        setMobileMenuState(isOpen);
    });

    mobileMenuClose?.addEventListener('click', () => setMobileMenuState(false));
    mobileMenuBackdrop?.addEventListener('click', () => setMobileMenuState(false));

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Dining Carousel
    const diningCarousel = document.getElementById('diningCarousel');
    const diningPrevBtn = document.getElementById('diningPrev');
    const diningNextBtn = document.getElementById('diningNext');

    if (diningCarousel && diningPrevBtn && diningNextBtn) {
        function getCardStep() {
            const card = diningCarousel.querySelector('.dining-card');
            if (!card) return 320;
            const style = window.getComputedStyle(diningCarousel);
            const gap = parseFloat(style.gap) || 28;
            return card.offsetWidth + gap;
        }

        function slideNext() {
            const maxScroll = diningCarousel.scrollWidth - diningCarousel.clientWidth;
            if (maxScroll <= 5) {
                const firstCard = diningCarousel.firstElementChild;
                if (firstCard) {
                    firstCard.style.opacity = '0';
                    firstCard.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        diningCarousel.appendChild(firstCard);
                        firstCard.style.opacity = '';
                        firstCard.style.transform = '';
                    }, 140);
                }
                return;
            }
            const step = getCardStep();
            if (diningCarousel.scrollLeft >= maxScroll - 15) {
                diningCarousel.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                diningCarousel.scrollBy({ left: step, behavior: 'smooth' });
            }
        }

        function slidePrev() {
            const maxScroll = diningCarousel.scrollWidth - diningCarousel.clientWidth;
            if (maxScroll <= 5) {
                const lastCard = diningCarousel.lastElementChild;
                if (lastCard) {
                    lastCard.style.opacity = '0';
                    lastCard.style.transform = 'scale(0.95)';
                    diningCarousel.prepend(lastCard);
                    setTimeout(() => {
                        lastCard.style.opacity = '';
                        lastCard.style.transform = '';
                    }, 40);
                }
                return;
            }
            const step = getCardStep();
            if (diningCarousel.scrollLeft <= 15) {
                diningCarousel.scrollTo({ left: maxScroll, behavior: 'smooth' });
            } else {
                diningCarousel.scrollBy({ left: -step, behavior: 'smooth' });
            }
        }

        diningNextBtn.addEventListener('click', (e) => { e.preventDefault(); slideNext(); });
        diningPrevBtn.addEventListener('click', (e) => { e.preventDefault(); slidePrev(); });

        diningCarousel.setAttribute('tabindex', '0');
        diningCarousel.setAttribute('aria-label', 'A Taste of the Tropics Carousel');
        diningCarousel.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') { e.preventDefault(); slideNext(); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); slidePrev(); }
        });
    }

    // BOOKING PAGE LOGIC
    const bookCheckin = document.getElementById('bookCheckin');
    const bookCheckout = document.getElementById('bookCheckout');
    const bookAdults = document.getElementById('bookAdults');
    const bookChildren = document.getElementById('bookChildren');
    const bookRoomType = document.getElementById('bookRoomType');
    const summaryRoomImage = document.getElementById('summaryRoomImage');
    const summaryRoomName = document.getElementById('summaryRoomName');
    const summaryRoomBadge = document.getElementById('summaryRoomBadge');
    const summaryDates = document.getElementById('summaryDates');
    const summaryNightsLabel = document.getElementById('summaryNightsLabel');
    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryTax = document.getElementById('summaryTax');
    const summaryResortFee = document.getElementById('summaryResortFee');
    const summaryTotal = document.getElementById('summaryTotal');
    const summaryGuestsText = document.getElementById('summaryGuestsText');
    const guestsContainer = document.getElementById('additionalGuestsContainer');
    const guestsSubtitle = document.getElementById('additionalGuestsSubtitle');
    const summaryExperiencesRow = document.getElementById('summaryExperiencesRow');
    const summaryExperiencesTotal = document.getElementById('summaryExperiencesTotal');
    const summaryExperiencesList = document.getElementById('summaryExperiencesList');
    const expCheckboxes = document.querySelectorAll('.booking-exp-checkbox');
    const expGuestSelects = document.querySelectorAll('.exp-guests-select');

    if (bookCheckin && bookCheckout && bookRoomType) {
        const todayStr = new Date().toISOString().split('T')[0];
        bookCheckin.setAttribute('min', todayStr);

        // Sync experience participant selectors with booking guest counts
        function syncExperienceGuestOptions() {
            const adults = parseInt(bookAdults.value) || 1;
            const children = parseInt(bookChildren.value) || 0;
            const totalGuests = adults + children;
            const maxSelectable = Math.max(4, totalGuests);

            expGuestSelects.forEach(select => {
                const currentVal = parseInt(select.value) || adults;
                select.innerHTML = '';
                for (let g = 1; g <= maxSelectable; g++) {
                    const opt = document.createElement('option');
                    opt.value = g;
                    opt.textContent = `${g} ${g === 1 ? 'Guest' : 'Guests'}`;
                    if (g === Math.min(currentVal, maxSelectable)) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                }
            });
        }

        function getSelectedExperiences() {
            const selected = [];
            let expTotalAmount = 0;

            expCheckboxes.forEach(cb => {
                const item = cb.closest('.booking-exp-item');
                const expId = cb.getAttribute('data-id');
                const guestSelector = document.getElementById(`guestSelector_${expId}`);
                const guestSelect = document.getElementById(`expGuests_${expId}`);
                const subtotalBadge = document.getElementById(`expSubtotal_${expId}`);

                if (cb.checked) {
                    if (item) item.classList.add('selected');
                    if (guestSelector) guestSelector.style.display = 'flex';

                    const price = parseFloat(cb.getAttribute('data-price')) || 0;
                    const name = cb.getAttribute('data-name') || '';
                    const guests = parseInt(guestSelect?.value) || 1;
                    const itemTotal = price * guests;

                    if (subtotalBadge) {
                        subtotalBadge.textContent = `₱${itemTotal.toLocaleString()}`;
                    }

                    expTotalAmount += itemTotal;
                    selected.push({
                        id: expId,
                        name: name,
                        guests: guests,
                        price: price,
                        total: itemTotal,
                    });
                } else {
                    if (item) item.classList.remove('selected');
                    if (guestSelector) guestSelector.style.display = 'none';
                }
            });

            return { selected, total: expTotalAmount };
        }

        expCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateBookingSummary();
            });
        });

        expGuestSelects.forEach(sel => {
            sel.addEventListener('change', () => {
                updateBookingSummary();
            });
        });

        bookCheckin.addEventListener('change', () => {
            bookCheckout.setAttribute('min', bookCheckin.value);
            if (bookCheckout.value && bookCheckout.value < bookCheckin.value) {
                bookCheckout.value = bookCheckin.value;
            }
            updateBookingSummary();
        });

        bookCheckout.addEventListener('change', updateBookingSummary);
        bookRoomType.addEventListener('change', updateBookingSummary);

        // Listen to guest count changes
        bookAdults.addEventListener('change', () => {
            updateAdditionalGuests();
            syncExperienceGuestOptions();
            updateBookingSummary();
        });
        bookChildren.addEventListener('change', () => {
            updateAdditionalGuests();
            syncExperienceGuestOptions();
            updateBookingSummary();
        });

        // Room option list click-to-select
        const roomOptionItems = document.querySelectorAll('.room-option-item');
        roomOptionItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                bookRoomType.selectedIndex = index + 1; // +1 because of the disabled placeholder
                roomOptionItems.forEach(el => el.classList.remove('selected'));
                item.classList.add('selected');
                updateBookingSummary();
            });
        });

        // Dynamic Additional Guests 
        function updateAdditionalGuests() {
            const adults = parseInt(bookAdults.value) || 1;
            const children = parseInt(bookChildren.value) || 0;
            const totalGuests = adults + children;
            const additionalCount = Math.max(0, totalGuests - 1); // minus the primary guest

            // Save existing values
            const existingInputs = guestsContainer.querySelectorAll('input[name="additional_guest[]"]');
            const existingValues = [];
            existingInputs.forEach(inp => existingValues.push(inp.value));

            // Clear container
            guestsContainer.innerHTML = '';

            if (additionalCount === 0) {
                guestsSubtitle.textContent = 'No additional guests needed for this booking.';
                return;
            }

            guestsSubtitle.textContent = `Add the names of ${additionalCount} other guest${additionalCount > 1 ? 's' : ''} staying with you.`;

            for (let i = 0; i < additionalCount; i++) {
                const guestNum = i + 2; // Guest 2, 3, 4, etc.
                const group = document.createElement('div');
                group.className = 'booking-field-group additional-guest-field';
                group.innerHTML = `
                    <div class="booking-field-outlined">
                        <label>Guest ${guestNum} of ${totalGuests}</label>
                        <input type="text" name="additional_guest[]" placeholder="Enter guest's full name" value="${existingValues[i] || ''}">
                    </div>
                `;
                guestsContainer.appendChild(group);
            }
        }

        // Initialize additional guests and experience guest options on load
        updateAdditionalGuests();
        syncExperienceGuestOptions();

        // Update Booking Summary
        function updateBookingSummary() {
            const selectedOption = bookRoomType.options[bookRoomType.selectedIndex];
            const price = parseFloat(selectedOption?.getAttribute('data-price')) || 0;
            const roomName = selectedOption?.getAttribute('data-name') || '-';
            const badge = selectedOption?.getAttribute('data-badge') || '-';
            const image = selectedOption?.getAttribute('data-image') || '';

            // Update room info
            if (summaryRoomName) summaryRoomName.textContent = roomName;
            if (summaryRoomBadge) summaryRoomBadge.textContent = badge;

            // Update room image
            if (summaryRoomImage) {
                if (image) {
                    summaryRoomImage.innerHTML = `<img src="${image}" alt="${roomName}" loading="lazy">`;

                    summaryRoomImage.onclick = () => {
                        const lightbox = document.getElementById('imageLightbox');
                        const lightboxImage = document.getElementById('lightboxImage');

                        if (lightbox && lightboxImage) {
                            lightboxImage.src = image;
                            lightbox.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }
                    };
                } else {
                    summaryRoomImage.innerHTML = '<span class="summary-room-placeholder">[ Selected Room Image ]</span>';
                    summaryRoomImage.onclick = null;
                }
            }

            // Calculate nights
            let nights = 0;
            if (bookCheckin.value && bookCheckout.value) {
                const ci = new Date(bookCheckin.value);
                const co = new Date(bookCheckout.value);
                const diff = co - ci;
                nights = Math.max(0, Math.round(diff / (1000 * 60 * 60 * 24)));
            }

            // Update dates display
            if (summaryDates) {
                if (bookCheckin.value && bookCheckout.value && nights > 0) {
                    const ciDate = new Date(bookCheckin.value);
                    const coDate = new Date(bookCheckout.value);
                    const opts = { month: 'short', day: 'numeric', year: 'numeric' };
                    summaryDates.innerHTML = `${ciDate.toLocaleDateString('en-US', opts)} to ${coDate.toLocaleDateString('en-US', opts)}<br>${nights} Night${nights > 1 ? 's' : ''}`;
                } else {
                    summaryDates.innerHTML = '<span>-</span>';
                }
            }

            // Update guests display
            if (summaryGuestsText) {
                const adults = parseInt(bookAdults.value) || 1;
                const children = parseInt(bookChildren.value) || 0;
                const parts = [];
                parts.push(`${adults} Adult${adults > 1 ? 's' : ''}`);
                parts.push(`${children} Child${children !== 1 ? 'ren' : ''}`);
                summaryGuestsText.textContent = parts.join(', ');
            }

            // Calculate experiences
            const expData = getSelectedExperiences();
            const experiencesTotal = expData.total;

            // Calculate prices
            const subtotal = price * nights;
            const tax = Math.round(subtotal * 0.12);
            const resortFee = 500;
            const total = subtotal + tax + resortFee + experiencesTotal;

            // Format currency
            const fmt = (n) => '₱' + n.toLocaleString();

            if (summaryNightsLabel) summaryNightsLabel.textContent = `${fmt(price)} × ${nights} night${nights > 1 ? 's' : ''}`;
            if (summarySubtotal) summarySubtotal.textContent = fmt(subtotal);
            if (summaryTax) summaryTax.textContent = fmt(tax);
            if (summaryResortFee) summaryResortFee.textContent = fmt(resortFee);

            // Update experiences row in summary
            if (summaryExperiencesRow && summaryExperiencesTotal && summaryExperiencesList) {
                if (expData.selected.length > 0) {
                    summaryExperiencesRow.style.display = 'flex';
                    summaryExperiencesTotal.textContent = fmt(experiencesTotal);
                    summaryExperiencesList.style.display = 'flex';
                    summaryExperiencesList.innerHTML = expData.selected.map(item => `
                        <div class="summary-exp-line">
                            <span>- ${item.name} (${item.guests} ${item.guests === 1 ? 'guest' : 'guests'})</span>
                            <span>${fmt(item.total)}</span>
                        </div>
                    `).join('');
                } else {
                    summaryExperiencesRow.style.display = 'none';
                    summaryExperiencesList.style.display = 'none';
                    summaryExperiencesList.innerHTML = '';
                }
            }

            if (summaryTotal) summaryTotal.textContent = fmt(total);

            // Highlight selected room in list
            const roomOptionItems = document.querySelectorAll('.room-option-item');
            roomOptionItems.forEach((el, i) => {
                el.classList.toggle('selected', (bookRoomType.selectedIndex - 1) === i);
            });
        }

        updateBookingSummary();

        // Confirm Booking 
        const confirmBtn = document.getElementById('btnConfirmBooking');
        const modalOverlay = document.getElementById('bookingModalOverlay');
        const modalDetails = document.getElementById('bookingModalDetails');

        if (confirmBtn) {
            confirmBtn.addEventListener('click', async () => {
                // Gather all values
                const fullName = document.getElementById('guestFullName')?.value.trim() || '';
                const email = document.getElementById('guestEmail')?.value.trim() || '';
                const phone = document.getElementById('guestPhone')?.value.trim() || '';
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || '';
                const specialRequests = document.getElementById('specialRequests')?.value.trim() || '';

                const selectedOption = bookRoomType.options[bookRoomType.selectedIndex];
                const roomName = selectedOption?.getAttribute('data-name') || '';
                const roomId = selectedOption?.getAttribute('data-id') || null;
                const roomPrice = parseFloat(selectedOption?.getAttribute('data-price')) || 0;

                const adults = parseInt(bookAdults.value) || 1;
                const children = parseInt(bookChildren.value) || 0;

                // Collect additional guest names
                const guestInputs = guestsContainer.querySelectorAll('input[name="additional_guest[]"]');
                const additionalGuests = [];
                guestInputs.forEach(inp => {
                    if (inp.value.trim()) additionalGuests.push(inp.value.trim());
                });

                // Client-side validation
                const errors = [];
                if (!bookCheckin.value) errors.push('Please select a check-in date.');
                if (!bookCheckout.value) errors.push('Please select a check-out date.');
                if (bookCheckin.value && bookCheckout.value && bookCheckin.value >= bookCheckout.value) {
                    errors.push('Check-out must be after check-in.');
                }
                if (!roomName) errors.push('Please select a room type.');
                if (!fullName) errors.push('Please enter your full name.');
                if (!email) errors.push('Please enter your email address.');
                if (!phone) errors.push('Please enter your phone number.');

                // Validate additional guests have names filled
                const expectedAdditional = (adults + children) - 1;
                if (expectedAdditional > 0) {
                    const filledGuests = additionalGuests.length;
                    if (filledGuests < expectedAdditional) {
                        errors.push(`Please fill in the names of all ${expectedAdditional} additional guest${expectedAdditional > 1 ? 's' : ''}.`);
                    }
                }

                if (errors.length > 0) {
                    alert(errors.join('\n'));
                    return;
                }

                // Calculate total 
                let nights = 0;
                if (bookCheckin.value && bookCheckout.value) {
                    const ci = new Date(bookCheckin.value);
                    const co = new Date(bookCheckout.value);
                    nights = Math.max(0, Math.round((co - ci) / (1000 * 60 * 60 * 24)));
                }
                const expData = getSelectedExperiences();
                const experiencesTotal = expData.total;
                const subtotal = roomPrice * nights;
                const tax = Math.round(subtotal * 0.12);
                const resortFee = 500;
                const total = subtotal + tax + resortFee + experiencesTotal;

                // Submit to server
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Processing...';

                try {
                    const apiEndpoint = '/api/booking';
                    const response = await fetch(apiEndpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            full_name: fullName,
                            email: email,
                            phone: phone,
                            room_type: roomName,
                            room_id: roomId,
                            checkin_date: bookCheckin.value,
                            checkout_date: bookCheckout.value,
                            adults: adults,
                            children: children,
                            additional_guests: additionalGuests,
                            payment_method: paymentMethod,
                            special_requests: specialRequests,
                            experiences: expData.selected,
                            total_amount: total,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Show confirmation modal
                        const fmt = (n) => '₱' + n.toLocaleString();
                        const ciDate = new Date(bookCheckin.value);
                        const coDate = new Date(bookCheckout.value);
                        const dateOpts = { month: 'short', day: 'numeric', year: 'numeric' };

                        if (modalDetails) {
                            let expModalHtml = '';
                            if (expData.selected.length > 0) {
                                const expNames = expData.selected.map(e => `${e.name} (${e.guests})`).join(', ');
                                expModalHtml = `
                                    <div class="modal-detail-row">
                                        <span class="modal-detail-label">Experiences</span>
                                        <span class="modal-detail-value">${expNames}</span>
                                    </div>
                                `;
                            }

                            modalDetails.innerHTML = `
                                <div class="modal-detail-row">
                                    <span class="modal-detail-label">Reservation ID</span>
                                    <span class="modal-detail-value">#${data.reservation_id}</span>
                                </div>
                                <div class="modal-detail-row">
                                    <span class="modal-detail-label">Room</span>
                                    <span class="modal-detail-value">${roomName}</span>
                                </div>
                                <div class="modal-detail-row">
                                    <span class="modal-detail-label">Dates</span>
                                    <span class="modal-detail-value">${ciDate.toLocaleDateString('en-US', dateOpts)} to ${coDate.toLocaleDateString('en-US', dateOpts)}</span>
                                </div>
                                <div class="modal-detail-row">
                                    <span class="modal-detail-label">Guests</span>
                                    <span class="modal-detail-value">${adults} Adult${adults > 1 ? 's' : ''}, ${children} Child${children !== 1 ? 'ren' : ''}</span>
                                </div>
                                ${expModalHtml}
                                <div class="modal-detail-row modal-detail-total">
                                    <span class="modal-detail-label">Total</span>
                                    <span class="modal-detail-value">${fmt(total)}</span>
                                </div>
                            `;
                        }

                        if (modalOverlay) {
                            modalOverlay.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }
                    } else {
                        alert(data.message || 'Something went wrong. Please try again.');
                    }
                } catch (err) {
                    alert('Network error. Please check your connection and try again.');
                } finally {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = 'Confirm Booking &rarr;';
                }
            });
        }

        // Close modal on overlay click
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    modalOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        // Lightbox close handling
        const lightboxOverlay = document.getElementById('imageLightbox');
        const lightboxClose = document.getElementById('lightboxClose');
        if (lightboxOverlay) {
            lightboxOverlay.addEventListener('click', (e) => {
                if (e.target === lightboxOverlay || e.target === lightboxClose) {
                    lightboxOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }
    }
});
