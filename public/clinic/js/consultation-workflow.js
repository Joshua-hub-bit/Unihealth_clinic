'use strict';

(function () {
    var activeNurseConsultationId = null;
    var nurseTranscriptionPollTimer = null;
    var nurseTranscriptionTimerInterval = null;
    var activeNurseQueueRole = 'nurse';
    var activeNurseQueuePage = 1;
    var nurseQueueSearchTimer = null;
    var nurseQueueLoadToken = 0;
    var studentRecording = null;
    var studentRecordingStarting = false;
    var studentRecordingChunks = [];
    var studentRecordingBlob = null;
    var studentRecordingDurationSeconds = null;
    var studentRecordingStartedAt = null;
    var studentRecordingTimerInterval = null;
    var studentRecordingUrl = null;
    var studentRecordingStream = null;
    var studentRecordingFileName = null;

    function element(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined && text !== null) { node.textContent = String(text); }
        return node;
    }

    function priorityLevel(priority) {
        var value = String(priority || '').trim().toLowerCase();
        var levels = {
            'priority 1': 'HIGH',
            '1': 'HIGH',
            high: 'HIGH',
            'priority 2': 'MEDIUM',
            '2': 'MEDIUM',
            medium: 'MEDIUM',
            'priority 3': 'LOW',
            '3': 'LOW',
            low: 'LOW'
        };

        return levels[value] || null;
    }

    function priorityIndicator(priority) {
        var level = priorityLevel(priority);
        var modifier = level ? level.toLowerCase() : 'unassigned';

        return element(
            'span',
            'cw-priority-indicator cw-priority-indicator--' + modifier,
            level || 'Not assigned'
        );
    }

    async function openAppointmentsAfterAcceptance(role) {
        var isNurseHead = role === 'nurse_head';
        var pageId = isNurseHead ? 'appointments' : 'queue';
        var appointmentNav = document.querySelector(
            isNurseHead
                ? '.uh-nav-item[data-title="Appoinments"], .uh-nav-item[data-title="Appointments"]'
                : '.uh-nav-item[data-title="Appointments"]'
        );

        if (typeof window.uhShowPage === 'function') {
            window.uhShowPage(pageId, appointmentNav);
        }

        if (isNurseHead) {
            if (typeof window.nhRenderAppointments === 'function') {
                await window.nhRenderAppointments();
            }
            if (typeof window.nhLoadDashboardStats === 'function') {
                await window.nhLoadDashboardStats();
            }
            return;
        }

        if (typeof window.loadQueueWithFallback === 'function') {
            await window.loadQueueWithFallback();
        } else if (typeof window.nLoadQueue === 'function') {
            window.nLoadQueue();
        }
        if (typeof window.loadStats === 'function') {
            await window.loadStats();
        }
    }

    function notify(message, type) {
        if (typeof window.uhToast === 'function') {
            window.uhToast(message, type || 'info');
        } else if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'info');
        } else {
            window.uhAlert(message, { title: 'Consultation update' });
        }
    }

    function reportError(error) {
        notify(error && error.message ? error.message : 'The consultation request could not be completed.', 'error');
    }

    function handleMissingConsultation(error, role) {
        if (!error || error.status !== 404) { return false; }

        notify('This consultation is no longer available in your queue. The list has been refreshed.', 'info');
        if (role === 'nurse' || role === 'nurse_head') {
            window.cwBackToQueue(role);
        } else {
            var detail = document.getElementById('cw-physician-detail');
            var queue = document.getElementById('cw-physician-queue');
            if (detail) { detail.hidden = true; }
            if (queue) { queue.hidden = false; }
            loadQueue('physician');
        }

        return true;
    }

    async function request(path, options) {
        var settings = options || {};
        var headers = new Headers(settings.headers || {});
        headers.set('Accept', 'application/json');
        if (settings.body && !(settings.body instanceof FormData)) {
            headers.set('Content-Type', 'application/json');
            settings.body = JSON.stringify(settings.body);
        }

        var response = await fetch('/api/clinic/' + path, {
            credentials: 'same-origin',
            ...settings,
            headers: headers
        });
        var data = await response.json().catch(function () { return {}; });
        if (!response.ok) {
            var message = data.message || data.error || (data.errors && Object.values(data.errors).flat()[0]) || 'Request failed.';
            if (response.status === 404 && /(?:^|\/)consultations\/\d+(?:\/|$)/.test(path)) {
                message = 'This consultation is no longer available.';
            }
            var error = new Error(message);
            error.status = response.status;
            throw error;
        }

        return data;
    }

    function addButton(parent, label, callback, className) {
        var button = element('button', className || 'uh-btn uh-btn-outline uh-btn-sm', label);
        button.type = 'button';
        button.addEventListener('click', callback);
        parent.appendChild(button);
        return button;
    }

    function addDetail(parent, label, value) {
        var block = element('div', 'cw-detail-block');
        block.appendChild(element('strong', '', label));
        block.appendChild(element('p', '', value || 'Not provided.'));
        parent.appendChild(block);
    }

    function renderTranscriptionSummary(parent, record, consultationId, apiPrefix) {
        var transcription = record.transcription;
        var hasVoiceRecording = Boolean(record.student_voice_path);
        var section = element('section', 'cw-transcription-summary');
        var heading = element('div', 'cw-transcription-heading');
        var title = element('div');
        title.appendChild(element('h3', '', hasVoiceRecording ? 'Transcription' : 'Consultation request summary'));
        if (!hasVoiceRecording) {
            title.appendChild(element('p', 'cw-muted', 'A quick guide to the concern and details shared by the student.'));
        }

        var transcriptionStatus = transcription && transcription.status;
        var isTranscriptionProcessing = record.status === 'audio_processing' || transcriptionStatus === 'processing';
        var isTranscriptionStarted = transcription && transcription.processing_elapsed_seconds !== null
            && transcription.processing_elapsed_seconds !== undefined;
        var badgeText = isTranscriptionProcessing
            ? 'Transcription processing'
            : transcriptionStatus === 'failed'
                ? 'Transcription failed'
                : transcriptionStatus === 'nurse_review'
                    ? 'Ready for nurse review'
                    : 'Student concern';
        var status = element('div', 'cw-transcription-state');
        status.appendChild(element('span', 'cw-badge', badgeText));
        var hasRecording = Boolean(record.student_voice_path || record.official_audio_path);
        var canManuallyTranscribe = hasRecording && (
            (record.status === 'transcription_failed' && transcriptionStatus === 'failed')
            || (record.status === 'submitted'
                && !record.accepted_at
                && !record.appointment_id
                && (!transcriptionStatus || ['failed', 'pending', 'queued'].indexOf(transcriptionStatus) !== -1))
            || (record.status === 'audio_processing'
                && transcriptionStatus === 'processing'
                && transcription.can_restart_unstarted === true)
        );
        if (canManuallyTranscribe) {
            var retryLabel = transcriptionStatus === 'failed' ? 'Retry transcription' : 'Transcribe recording';
            var transcribeButton = addButton(status, retryLabel, async function () {
                transcribeButton.disabled = true;
                transcribeButton.textContent = 'Starting…';
                try {
                    await request(apiPrefix + '/consultations/' + consultationId + '/recording/transcribe', { method: 'POST', body: {} });
                    notify('The recording is being transcribed now.', 'success');
                    await openNurseConsultation(consultationId, activeNurseQueueRole);
                } catch (error) {
                    reportError(error);
                    transcribeButton.disabled = false;
                    transcribeButton.textContent = retryLabel;
                }
            }, 'uh-btn uh-btn-primary uh-btn-xs cw-transcription-retry-button');
        }
        heading.append(title, status);
        section.appendChild(heading);

        var transcriptText = transcription && (hasVoiceRecording
            ? transcription.raw_transcript
            : (transcription.ai_draft || transcription.raw_transcript));
        var aiSummary = record.ai_summary || {};
        var hasAiSummary = !record.nurse_summary
            && aiSummary.status === 'ready'
            && typeof aiSummary.summary === 'string'
            && aiSummary.summary !== '';
        var summaryLead = element('div', 'cw-summary-lead');
        if (hasVoiceRecording) {
            if (typeof transcriptText === 'string' && transcriptText.trim() !== '') {
                summaryLead.appendChild(element('p', '', transcriptText));
            }
        } else {
            var hasAudioWithoutWrittenConcern = record.student_voice_path
                && (!record.concern || record.concern === 'Audio recording submitted without written concern.');
            var requestText = hasAudioWithoutWrittenConcern ? transcriptText : record.concern;
            var requestPreview = String(requestText || '');
            if (requestPreview.length > 220) {
                var previewEnd = requestPreview.lastIndexOf(' ', 220);
                requestPreview = requestPreview.slice(0, previewEnd > 160 ? previewEnd : 220).trimEnd() + '…';
            }
            var originalSummary = [
                requestPreview,
                record.symptom_onset ? 'Symptoms began: ' + record.symptom_onset + '.' : '',
                record.severity ? 'The student says it feels like a ' + record.severity + ' out of 10.' : ''
            ].filter(Boolean).join(' ');
            var isSummaryPending = ['pending', 'waiting'].indexOf(aiSummary.status) !== -1;
            var requestSummary = record.nurse_summary || (hasAiSummary ? aiSummary.summary : isSummaryPending ? '' : originalSummary);
            summaryLead.appendChild(element('strong', '', record.nurse_summary
                ? 'Nurse’s summary'
                : hasAiSummary
                    ? 'AI-generated summary'
                    : aiSummary.status === 'pending'
                        ? 'Student report · AI summary in progress'
                        : aiSummary.status === 'waiting'
                            ? 'Student report · waiting for transcription'
                            : hasAudioWithoutWrittenConcern && transcriptText
                                ? 'Voice request transcript — check against recording'
                                : 'What the student reported'));
            summaryLead.appendChild(element('p', '', requestSummary || (isTranscriptionProcessing
                ? 'The recording is being transcribed. A short summary will appear here when ready.'
                : aiSummary.status === 'pending'
                    ? 'A short AI summary is being prepared from the transcript.'
                : aiSummary.status === 'waiting'
                    ? 'The recording is being transcribed. A short complaint summary will appear when ready.'
                    : transcriptionStatus === 'pending'
                        ? 'The recording is waiting to be transcribed. A nurse can retry transcription if it does not complete.'
                        : transcriptionStatus === 'failed'
                            ? 'There is no written concern yet. Listen to the recording or use Transcribe recording to understand the request.'
                            : 'No concern has been added yet. Review the request with the student.')));
            var writtenConcern = hasAudioWithoutWrittenConcern ? '' : record.concern;
            var originalTranscript = transcriptText && transcriptText !== writtenConcern ? transcriptText : '';
            var originalSources = [
                [writtenConcern ? 'Original student concern' : '', writtenConcern],
                [originalTranscript ? 'Original audio transcript' : '', originalTranscript]
            ].filter(function (source) { return source[1]; });
            if (originalSources.length) {
                var sourceDetails = document.createElement('details');
                sourceDetails.className = 'cw-transcript-details';
                sourceDetails.appendChild(element('summary', '', originalSources.length > 1
                    ? 'View original concern and transcript'
                    : originalSources[0][0] === 'Original student concern'
                        ? 'View original concern'
                        : 'View full transcript'));
                originalSources.forEach(function (source) {
                    var sourceBlock = element('div', 'cw-ai-summary-source cw-transcript-text');
                    sourceBlock.appendChild(element('strong', '', source[0]));
                    sourceBlock.appendChild(element('p', '', source[1]));
                    sourceDetails.appendChild(sourceBlock);
                });
                summaryLead.appendChild(sourceDetails);
            }
            if (hasAiSummary) {
                summaryLead.appendChild(element(
                    'small',
                    'cw-ai-summary-disclaimer',
                    'AI-generated summary. Verify it against the original concern and transcript before making clinical decisions.'
                ));
            } else if (aiSummary.status === 'unavailable') {
                summaryLead.appendChild(element(
                    'small',
                    'cw-ai-summary-disclaimer',
                    'AI summary is unavailable. Review the original concern and transcript.'
                ));
            }
        }
        if (summaryLead.childElementCount) {
            section.appendChild(summaryLead);
        }

        if (isTranscriptionProcessing) {
            var liveIndicator = element('div', 'cw-transcription-live');
            liveIndicator.setAttribute('role', 'status');
            liveIndicator.setAttribute('aria-live', 'polite');
            var waveform = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            waveform.setAttribute('class', 'cw-ecg');
            waveform.setAttribute('viewBox', '0 0 240 48');
            waveform.setAttribute('aria-hidden', 'true');
            var trace = 'M0 24 H34 L42 24 L48 14 L54 35 L63 5 L72 43 L81 24 H112 L120 24 L126 15 L132 34 L141 5 L150 43 L159 24 H190 L198 24 L204 15 L210 34 L219 5 L228 43 L237 24 H240';
            ['cw-ecg-track', 'cw-ecg-pulse'].forEach(function (className) {
                var polyline = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                polyline.setAttribute('class', className);
                polyline.setAttribute('d', trace);
                waveform.appendChild(polyline);
            });
            var liveLabel = element('span', 'cw-transcription-live-label', isTranscriptionStarted
                ? 'Transcribing audio…'
                : 'Waiting for transcription queue…');
            var timer = element('span', 'cw-transcription-timer');
            timer.setAttribute('aria-hidden', 'true');
            timer.dataset.started = isTranscriptionStarted ? 'true' : 'false';
            timer.dataset.elapsedSeconds = String(transcription && transcription.processing_elapsed_seconds || 0);
            timer.dataset.totalSeconds = transcription && transcription.estimated_total_seconds !== null
                && transcription.estimated_total_seconds !== undefined
                ? String(transcription.estimated_total_seconds)
                : '';
            timer.dataset.sampleCount = String(transcription && transcription.eta_sample_count || 0);
            timer.dataset.updatedAt = String(Date.now());
            liveIndicator.append(waveform, liveLabel, timer);
            updateTranscriptionTimer(timer);
            section.appendChild(liveIndicator);
        } else if (transcription && transcription.processing_duration_seconds) {
            section.appendChild(element(
                'p',
                'cw-transcription-complete-time',
                'Transcription finished in ' + formatTranscriptionTime(transcription.processing_duration_seconds) + '.'
            ));
        }

        var overview = element('div', 'cw-summary-facts');
        [
            ['When did it start?', record.symptom_onset || 'Not shared', 'The student’s description of when symptoms began.'],
            ['How severe does it feel?', record.severity ? record.severity + ' / 10' : 'Not shared', 'The student’s own rating, from 1 (mild) to 10 (severe).'],
            ['Type of care requested', record.care_type === 'dental' ? 'Dental care' : 'Medical care', 'The kind of clinic visit requested.'],
            ['Initial triage level', priorityIndicator(record.triage_priority), 'An initial screening result for staff to review; it is not a diagnosis.']
        ].forEach(function (fact) {
            var item = element('div', 'cw-summary-fact');
            item.appendChild(element('strong', '', fact[0]));
            item.appendChild(typeof fact[1] === 'string' ? element('span', '', fact[1]) : fact[1]);
            item.appendChild(element('small', '', fact[2]));
            overview.appendChild(item);
        });
        section.appendChild(overview);

        if (isTranscriptionProcessing || transcriptionStatus === 'failed') {
            var statusMessage = isTranscriptionProcessing
                ? isTranscriptionStarted
                    ? 'The recording is being processed by Groq. If it remains here for several minutes, check the transcription service and server logs.'
                    : transcription && transcription.can_restart_unstarted
                        ? 'This recording is still waiting from an earlier attempt. Select Transcribe recording to process it now.'
                        : 'The recording is waiting to start. The clinic host must have a Laravel queue worker running.'
                : 'The uploaded recording could not be transcribed. Use Transcribe recording to try again.';
            section.appendChild(element('p', 'cw-transcript-status', statusMessage));
        }

        var media = element('div', 'cw-private-media');
        if (record.student_voice_path) { addMediaLink(media, consultationId, 'student_voice', 'Listen to student’s voice message', apiPrefix); }
        if (record.student_photo_path) { addMediaLink(media, consultationId, 'student_photo', 'View request photo', apiPrefix); }
        if (record.official_audio_path) { addMediaLink(media, consultationId, 'official_audio', 'Play official recording', apiPrefix); }
        if (media.childElementCount) { section.appendChild(media); }

        parent.appendChild(section);
    }

    function renderConsultationRequest(parent, record) {
        var section = element('section', 'cw-request-form');
        var heading = element('div', 'cw-request-form-heading');
        heading.appendChild(element('h3', '', 'Consultation request form'));
        heading.appendChild(element('p', 'cw-muted', 'Student-submitted details for this request.'));
        section.appendChild(heading);

        var formFields = element('div', 'cw-request-fields');
        var concern = element('div', 'cw-request-field cw-request-field-wide');
        concern.appendChild(element('strong', '', 'Main concern'));
        concern.appendChild(element('p', '', record.concern === 'Audio recording submitted without written concern.'
            ? 'Voice request submitted. Review the summary and transcript on the right.'
            : record.concern || 'Not provided.'));
        formFields.appendChild(concern);

        [
            ['Consultation type', record.care_type === 'dental' ? 'Dental' : 'Medical'],
            ['Symptoms started', record.symptom_onset || 'Not provided'],
            ['Reported severity', record.severity ? record.severity + ' / 10' : 'Not provided']
        ].forEach(function (field) {
            var item = element('div', 'cw-request-field');
            item.appendChild(element('strong', '', field[0]));
            item.appendChild(element('span', '', field[1]));
            formFields.appendChild(item);
        });

        section.appendChild(formFields);
        parent.appendChild(section);
    }

    function formatDateTime(value) {
        return value ? window.uhFormatDateTime(value) : '';
    }

    function stageLabel(status) {
        var labels = {
            submitted: 'Submitted',
            nurse_reviewing: 'Nurse reviewing',
            consultation_scheduled: 'Consultation scheduled',
            consultation_in_progress: 'Consultation in progress',
            audio_processing: 'Preparing transcript',
            transcript_ready: 'Nurse transcript review',
            transcription_failed: 'Transcription needs retry',
            nurse_review_complete: 'Nurse review complete',
            pending_clinician_review: 'Waiting for clinician review',
            additional_information_requested: 'Clinic will follow up',
            in_person_followup: 'In-person follow-up required',
            completed: 'Completed',
            physician_approved: 'Clinician instructions ready',
            cancelled: 'Cancelled',
            declined: 'Declined'
        };

        return labels[status] || 'In progress';
    }

    function renderStudentConsultations(consultations) {
        var list = document.getElementById('cw-student-list');
        if (!list) { return; }
        list.replaceChildren();

        if (!consultations.length) {
            list.appendChild(element('p', 'cw-empty', 'You have not submitted a consultation request yet.'));
            return;
        }

        consultations.forEach(function (consultation) {
            var item = element('article', 'cw-item cw-student-item');

            // ── Left: info column ─────────────────────────────────────
            var info = element('div', 'cw-student-info');
            info.appendChild(element('h4', '', consultation.care_type === 'dental' ? 'Dental consultation' : 'Health consultation'));
            info.appendChild(element('p', 'cw-muted', consultation.concern));
            var submittedAt = formatDateTime(consultation.submitted_at);
            if (submittedAt) {
                info.appendChild(element('small', 'cw-muted cw-submitted-at', 'Submitted ' + submittedAt));
            }
            // Scheduled date lives in the info column, below submission time
            if (consultation.scheduled_at) {
                info.appendChild(element('small', 'cw-muted cw-student-consultation-schedule', 'Scheduled: ' + window.uhFormatDateTime(consultation.scheduled_at)));
            }

            // ── Right: actions column ─────────────────────────────────
            var controls = element('div', 'cw-actions cw-student-consultation-actions');

            // Status badge always at the top of the actions column
            controls.appendChild(element('span', 'cw-badge cw-student-consultation-status', consultation.scheduled_at
                ? 'Consultation scheduled'
                : stageLabel(consultation.status)));

            // Edit button
            if (consultation.can_edit) {
                addButton(controls, 'Edit request', async function () {
                    var concern = await window.uhPrompt('Update the concern you sent to the clinic.', {
                        title: 'Edit consultation request',
                        label: 'Written concern',
                        value: consultation.concern,
                        placeholder: 'Describe your health concern',
                        confirmText: 'Save changes',
                        cancelText: 'Keep request',
                        maxLength: 5000,
                        rows: 4
                    });
                    if (concern === null) { return; }
                    try {
                        await request('student/consultations/' + encodeURIComponent(consultation.id), {
                            method: 'PUT',
                            body: { concern: concern.trim() }
                        });
                        notify('Consultation request updated.', 'success');
                        await loadStudentConsultations();
                    } catch (error) {
                        reportError(error);
                    }
                });
            }

            // Delete button
            if (consultation.can_delete) {
                addButton(controls, 'Delete request', async function () {
                    var deleted = await deleteConsultation('student/consultations/' + encodeURIComponent(consultation.id), 'Delete this consultation request? This cannot be undone.');
                    if (deleted) { await loadStudentConsultations(); }
                }, 'uh-btn uh-btn-outline uh-btn-sm cw-delete-button');
            }

            item.append(info, controls);

            // Detail rows (span full width below both columns)
            if (consultation.decline_reason) {
                addDetail(item, 'Clinic decline reason', consultation.decline_reason);
            }
            if (consultation.nursing_advice) { addDetail(item, 'Nurse instructions', consultation.nursing_advice); }
            if (consultation.emergency_instructions) { addDetail(item, 'Urgent instructions', consultation.emergency_instructions); }
            if (consultation.diagnosis) { addDetail(item, 'Clinician diagnosis', consultation.diagnosis); }
            if (consultation.treatment_plan) { addDetail(item, 'Treatment plan', consultation.treatment_plan); }
            if (consultation.approved_instructions) { addDetail(item, 'Approved instructions', consultation.approved_instructions); }
            if (consultation.follow_up) { addDetail(item, 'Follow-up', consultation.follow_up); }
            list.appendChild(item);
        });
    }

    async function loadStudentConsultations() {
        var list = document.getElementById('cw-student-list');
        if (!list) { return; }
        list.replaceChildren(element('p', 'cw-empty', 'Loading your requests…'));
        try {
            var data = await request('student/consultations');
            renderStudentConsultations(data.consultations || []);
        } catch (error) { reportError(error); }
    }

    function updateStudentRecorder(status, isError) {
        var statusElement = document.getElementById('cw-record-status');
        if (!statusElement) { return; }
        statusElement.textContent = status;
        statusElement.classList.toggle('cw-recording-error', Boolean(isError));
    }

    function setStudentRecordingIndicator(isRecording) {
        var indicator = document.getElementById('cw-record-indicator');
        if (indicator) { indicator.hidden = !isRecording; }
    }

    function stopStudentMicrophone() {
        if (studentRecordingStream) {
            studentRecordingStream.getTracks().forEach(function (track) { track.stop(); });
            studentRecordingStream = null;
        }
    }

    function updateStudentRecordingTimer() {
        var timer = document.getElementById('cw-record-timer');
        if (timer && studentRecordingStartedAt !== null) {
            timer.textContent = formatTranscriptionTime(
                Math.floor((performance.now() - studentRecordingStartedAt) / 1000)
            );
        }
    }

    function resetStudentRecording() {
        if (studentRecordingTimerInterval) {
            window.clearInterval(studentRecordingTimerInterval);
            studentRecordingTimerInterval = null;
        }
        if (studentRecording) {
            studentRecording.ondataavailable = null;
            studentRecording.onerror = null;
            studentRecording.onstop = null;
            if (studentRecording.state !== 'inactive') {
                studentRecording.stop();
            }
        }
        studentRecording = null;
        studentRecordingStarting = false;
        studentRecordingChunks = [];
        studentRecordingBlob = null;
        studentRecordingDurationSeconds = null;
        studentRecordingStartedAt = null;
        studentRecordingFileName = null;
        stopStudentMicrophone();
        if (studentRecordingUrl) {
            URL.revokeObjectURL(studentRecordingUrl);
            studentRecordingUrl = null;
        }
        var preview = document.getElementById('cw-record-preview');
        var startButton = document.getElementById('cw-record-start');
        var stopButton = document.getElementById('cw-record-stop');
        var retakeButton = document.getElementById('cw-record-retake');
        var clearButton = document.getElementById('cw-record-clear');
        var timer = document.getElementById('cw-record-timer');
        setStudentRecordingIndicator(false);
        if (timer) { timer.textContent = '00:00'; }
        if (preview) {
            preview.pause();
            preview.removeAttribute('src');
            preview.load();
            preview.hidden = true;
        }
        if (startButton) { startButton.hidden = false; startButton.disabled = false; }
        if (stopButton) { stopButton.hidden = true; }
        if (retakeButton) { retakeButton.hidden = true; }
        if (clearButton) { clearButton.hidden = true; }
        updateStudentRecorder('Your recording will be stored privately and transcribed automatically for nurse review.');
    }

    function studentRecordingExtension(mimeType) {
        var normalizedMimeType = String(mimeType || '').toLowerCase();
        if (normalizedMimeType.indexOf('wav') !== -1) { return 'wav'; }
        if (normalizedMimeType.indexOf('mpeg') !== -1 || normalizedMimeType.indexOf('mp3') !== -1) { return 'mp3'; }
        if (normalizedMimeType.indexOf('mp4') !== -1) { return 'm4a'; }
        if (normalizedMimeType.indexOf('ogg') !== -1) { return 'ogg'; }
        if (normalizedMimeType.indexOf('flac') !== -1) { return 'flac'; }
        if (normalizedMimeType.indexOf('webm') !== -1) { return 'webm'; }
        return null;
    }

    function prepareStudentAudioPreview(audioBlob, fileName, statusMessage) {
        var preview = document.getElementById('cw-record-preview');
        var clearButton = document.getElementById('cw-record-clear');
        if (studentRecordingUrl) { URL.revokeObjectURL(studentRecordingUrl); }
        studentRecordingUrl = URL.createObjectURL(audioBlob);
        if (preview) {
            preview.src = studentRecordingUrl;
            preview.hidden = false;
            preview.onloadedmetadata = function () {
                if (Number.isFinite(preview.duration) && preview.duration > 0) {
                    studentRecordingDurationSeconds = Math.round(preview.duration);
                }
            };
        }
        studentRecordingFileName = fileName;
        if (clearButton) { clearButton.hidden = false; }
        updateStudentRecorder(statusMessage);
    }

    async function startStudentRecording() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof window.MediaRecorder === 'undefined') {
            updateStudentRecorder('Audio recording is not supported in this browser. Please type your concern or try a current browser.', true);
            return;
        }

        resetStudentRecording();
        studentRecordingStarting = true;
        var startButton = document.getElementById('cw-record-start');
        var stopButton = document.getElementById('cw-record-stop');
        if (startButton) { startButton.disabled = true; }

        try {
            studentRecordingStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            var supportedTypes = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
            var mimeType = supportedTypes.find(function (type) {
                return window.MediaRecorder.isTypeSupported(type);
            });
            studentRecording = new window.MediaRecorder(
                studentRecordingStream,
                mimeType ? { mimeType: mimeType } : undefined
            );
            studentRecordingChunks = [];
            studentRecording.ondataavailable = function (event) {
                if (event.data && event.data.size > 0) { studentRecordingChunks.push(event.data); }
            };
            studentRecording.onerror = function () {
                if (studentRecordingTimerInterval) {
                    window.clearInterval(studentRecordingTimerInterval);
                    studentRecordingTimerInterval = null;
                }
                stopStudentMicrophone();
                setStudentRecordingIndicator(false);
                if (startButton) { startButton.hidden = false; startButton.disabled = false; }
                if (stopButton) { stopButton.hidden = true; }
                updateStudentRecorder('The recording could not be completed. Please try again or type your concern.', true);
            };
            studentRecording.onstop = function () {
                if (studentRecordingTimerInterval) {
                    window.clearInterval(studentRecordingTimerInterval);
                    studentRecordingTimerInterval = null;
                }
                if (studentRecordingStartedAt !== null) {
                    studentRecordingDurationSeconds = Math.max(
                        1,
                        Math.round((performance.now() - studentRecordingStartedAt) / 1000)
                    );
                }
                studentRecordingStartedAt = null;
                studentRecordingStarting = false;
                setStudentRecordingIndicator(false);
                stopStudentMicrophone();
                studentRecordingBlob = new Blob(studentRecordingChunks, {
                    type: studentRecording.mimeType || (studentRecordingChunks[0] && studentRecordingChunks[0].type) || 'audio/webm'
                });
                studentRecordingChunks = [];

                if (studentRecordingBlob.size > 20 * 1024 * 1024) {
                    studentRecordingBlob = null;
                    if (startButton) { startButton.hidden = false; startButton.disabled = false; }
                    if (stopButton) { stopButton.hidden = true; }
                    updateStudentRecorder('This recording is over 20 MB. Please record a shorter message.', true);
                    return;
                }

                var preview = document.getElementById('cw-record-preview');
                var retakeButton = document.getElementById('cw-record-retake');
                studentRecordingFileName = 'consultation-recording.' + studentRecordingExtension(studentRecordingBlob.type);
                prepareStudentAudioPreview(
                    studentRecordingBlob,
                    studentRecordingFileName,
                    'Recording ready (' + formatTranscriptionTime(studentRecordingDurationSeconds || 0) + '). Play it back or record again before submitting.'
                );
                if (startButton) { startButton.hidden = true; }
                if (stopButton) { stopButton.hidden = true; }
                if (retakeButton) { retakeButton.hidden = false; }
            };
            studentRecording.start();
            studentRecordingStartedAt = performance.now();
            studentRecordingStarting = false;
            if (startButton) { startButton.hidden = true; startButton.disabled = false; }
            if (stopButton) { stopButton.hidden = false; }
            setStudentRecordingIndicator(true);
            studentRecordingTimerInterval = window.setInterval(updateStudentRecordingTimer, 250);
            updateStudentRecordingTimer();
            updateStudentRecorder('Recording… Select Stop recording when you are finished.');
        } catch (error) {
            studentRecordingStarting = false;
            setStudentRecordingIndicator(false);
            stopStudentMicrophone();
            if (startButton) { startButton.disabled = false; }
            var message = error && error.name === 'NotAllowedError'
                ? 'Microphone access was blocked. Allow access in your browser settings, or type your concern instead.'
                : 'Could not start microphone recording. Check your microphone and try again, or type your concern.';
            updateStudentRecorder(message, true);
        }
    }

    function stopStudentRecording() {
        if (studentRecording && studentRecording.state === 'recording') {
            studentRecording.stop();
        }
    }

    async function submitStudentRequest(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var concern = document.getElementById('cw-student-concern');
        var submitButton = form.querySelector('button[type="submit"]');
        var submitButtonText = submitButton ? submitButton.textContent : '';
        var hasRecording = Boolean(studentRecordingBlob);
        if (studentRecordingStarting || (studentRecording && studentRecording.state === 'recording')) {
            notify('Stop your recording and wait for it to be ready before submitting.', 'error');
            return;
        }
        if (!concern || !concern.value.trim()) {
            notify('Please enter a short description of your consultation.', 'error');
            if (concern) { concern.focus(); }
            return;
        }

        var data = new FormData(form);
        if (studentRecordingBlob) {
            data.append('voice_description', new File(
                [studentRecordingBlob],
                studentRecordingFileName || ('consultation-recording.' + (studentRecordingExtension(studentRecordingBlob.type) || 'webm')),
                { type: studentRecordingBlob.type }
            ));
            if (studentRecordingDurationSeconds) {
                data.append('voice_duration_seconds', String(studentRecordingDurationSeconds));
            }
        }
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting…';
        }
        if (studentRecordingBlob) {
            updateStudentRecorder('Submitting your recording securely…');
        }
        try {
            await request('student/consultations', { method: 'POST', body: data });
            form.reset();
            notify(
                hasRecording
                    ? 'Your concern was submitted. Your recording is being transcribed for the nurse team.'
                    : 'Your health concern was submitted to the nurse team.',
                'success'
            );
            await loadStudentConsultations();
        } catch (error) {
            reportError(error);
            if (studentRecordingBlob) {
                updateStudentRecorder('The submission failed. Your recording is still here; retry or clear it.', true);
            }
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = submitButtonText;
            }
        }
    }

    function renderQueue(listId, consultations, open, role, appointmentSlotOffset) {
        var list = document.getElementById(listId);
        if (!list) { return; }
        list.replaceChildren();

        if (!consultations.length) {
            list.appendChild(element('p', 'cw-empty', 'There are no consultation requests in this queue.'));
            return;
        }

        consultations.forEach(function (consultation) {
            var item = element('article', 'cw-item cw-queue-item');
            var info = element('div');
            var name = consultation.student && consultation.student.name
                ? consultation.student.name
                : consultation.student
                    ? [consultation.student.first_name, consultation.student.last_name].filter(Boolean).join(' ')
                    : 'Student';
            info.appendChild(element('h4', '', name));
            info.appendChild(element('p', 'cw-muted', consultation.concern));
            if (consultation.student && consultation.student.campus && consultation.student.campus.name) {
                info.appendChild(element('small', 'cw-muted', consultation.student.campus.name));
            }
            info.appendChild(priorityIndicator(consultation.triage_priority));
            info.appendChild(element('small', 'cw-muted', consultation.care_type || 'medical'));
            var submittedAt = formatDateTime(consultation.created_at || consultation.submitted_at);
            if (submittedAt) {
                info.appendChild(element('small', 'cw-muted cw-submitted-at', 'Submitted ' + submittedAt));
            }
            var controls = element('div', 'cw-actions');
            controls.appendChild(element('span', 'cw-badge', stageLabel(consultation.status)));
            addButton(controls, 'Open request', function () { open(consultation.id); });
            if (role === 'nurse' || role === 'nurse_head') {
                addConsultationDecisionActions(controls, consultation, role, function () {
                    return loadQueue(role);
                }, function () {
                    return loadQueue(role);
                });
            }
            if ((role === 'nurse' || role === 'nurse_head') && consultation.status !== 'nurse_review_complete') {
                addButton(controls, 'Delete request', async function () {
                    var prefix = role === 'nurse_head' ? 'nurse-head' : 'nurse';
                    var deleted = await deleteConsultation(prefix + '/consultations/' + encodeURIComponent(consultation.id), 'Delete this consultation request? This cannot be undone.');
                    if (deleted) { await loadQueue(role); }
                }, 'uh-btn uh-btn-outline uh-btn-sm cw-delete-button');
            }
            item.append(info, controls);
            list.appendChild(item);
        });
    }

    function renderQueuePagination(pagination, role) {
        var container = document.getElementById('cw-queue-pagination');
        if (!container) { return; }
        container.replaceChildren();

        var currentPage = pagination.current_page || 1;
        var lastPage = pagination.last_page || 1;
        var total = pagination.total || 0;
        var rangeStart = pagination.from || 0;
        var rangeEnd = pagination.to || 0;
        var summary = element('span', 'cw-pagination-summary',
            'Showing ' + rangeStart + '–' + rangeEnd + ' of ' + total + ' requests');
        var controls = element('div', 'cw-pagination-controls');
        var previous = addButton(controls, 'Previous', function () {
            loadQueue(role, currentPage - 1);
        }, 'uh-btn uh-btn-outline uh-btn-sm');
        previous.disabled = currentPage <= 1;

        controls.appendChild(element('span', 'cw-pagination-page', 'Page ' + currentPage + ' of ' + lastPage));

        var next = addButton(controls, 'Next', function () {
            loadQueue(role, currentPage + 1);
        }, 'uh-btn uh-btn-outline uh-btn-sm');
        next.disabled = currentPage >= lastPage;

        container.append(summary, controls);
    }

    function addConsultationDecisionActions(container, record, role, onComplete, onAccepted) {
        if (record.accepted_at
            || record.appointment_id
            || !['submitted', 'audio_processing', 'transcript_ready', 'transcription_failed'].includes(record.status)) {
            return;
        }

        var prefix = role === 'nurse_head' ? 'nurse-head' : 'nurse';
        addButton(container, 'Decline request', async function () {
            var reason = await window.uhPrompt('Enter the reason for declining this consultation request.', {
                title: 'Decline consultation request?',
                label: 'Reason for decline',
                placeholder: 'Provide a reason the student can see',
                confirmText: 'Decline request',
                cancelText: 'Keep request',
                maxLength: 1000,
                rows: 4,
                danger: true
            });
            if (reason === null) { return; }
            if (!reason.trim()) {
                notify('A decline reason is required.', 'error');
                return;
            }
            try {
                await request(prefix + '/consultations/' + record.id + '/decline', {
                    method: 'POST',
                    body: { reason: reason.trim() }
                });
                notify('Consultation request declined. The student can see the reason.', 'success');
                await onComplete();
            } catch (error) { reportError(error); }
        }, 'uh-btn uh-btn-outline uh-btn-sm cw-delete-button');
    }

    async function loadQueue(role, page) {
        var isNurseQueue = role === 'nurse' || role === 'nurse_head';
        if (isNurseQueue) { activeNurseQueueRole = role; }
        if (isNurseQueue && Number.isInteger(page) && page > 0) {
            activeNurseQueuePage = page;
        }
        var requestToken = isNurseQueue ? ++nurseQueueLoadToken : null;
        var prefix = role === 'nurse_head' ? 'nurse-head' : role === 'nurse' ? 'nurse' : 'physician';
        var listId = isNurseQueue ? 'cw-nurse-queue' : 'cw-physician-queue';
        var list = document.getElementById(listId);
        if (!list) { return; }
        list.replaceChildren(element('p', 'cw-empty', 'Loading requests…'));
        try {
            var query = new URLSearchParams();
            var campusFilter = document.getElementById('cw-queue-campus');
            var priorityFilter = document.getElementById('cw-queue-priority');
            var studentSearch = document.getElementById('cw-queue-student-search');
            if (isNurseQueue && campusFilter && campusFilter.value) {
                query.set('campus_id', campusFilter.value);
            }
            if (isNurseQueue && priorityFilter && priorityFilter.value) {
                query.set('priority', priorityFilter.value);
            }
            if (isNurseQueue && studentSearch && studentSearch.value.trim()) {
                query.set('search', studentSearch.value.trim());
            }
            if (isNurseQueue) {
                query.set('page', String(activeNurseQueuePage));
            }
            var queryString = query.toString();
            var data = await request(prefix + '/consultations' + (queryString ? '?' + queryString : ''));
            if (isNurseQueue && requestToken !== nurseQueueLoadToken) { return; }
            var consultations = data.consultations || [];
            var pagination = data.pagination || { current_page: 1, last_page: 1, total: consultations.length, from: consultations.length ? 1 : null, to: consultations.length || null };
            if (isNurseQueue && pagination.last_page > 0 && pagination.current_page > pagination.last_page) {
                loadQueue(role, pagination.last_page);
                return;
            }
            if (isNurseQueue) { activeNurseQueuePage = pagination.current_page; }
            renderQueue(listId, consultations, function (id) {
                if (isNurseQueue) { openNurseConsultation(id, role); }
                else { openPhysicianConsultation(id); }
            }, role, isNurseQueue ? (pagination.current_page - 1) * pagination.per_page : 0);
            if (isNurseQueue) {
                renderQueuePagination(pagination, role);
                var count = document.getElementById('cw-consultation-count');
                if (count) { count.textContent = String(pagination.total); }
            }
        } catch (error) {
            if (!isNurseQueue || requestToken === nurseQueueLoadToken) { reportError(error); }
        }
    }

    async function loadConsultationCampuses(role) {
        var campusFilter = document.getElementById('cw-queue-campus');
        if (!campusFilter) {
            await loadQueue(role);
            return;
        }

        try {
            var prefix = role === 'nurse_head' ? 'nurse-head' : 'nurse';
            var data = await request(prefix + '/consultations/campuses');
            var campuses = data.campuses || [];
            campusFilter.replaceChildren();

            if (role === 'nurse_head') {
                var allOption = element('option', '', 'All campuses');
                allOption.value = '';
                campusFilter.appendChild(allOption);
            }

            campuses.forEach(function (campus) {
                var option = element('option', '', campus.name);
                option.value = String(campus.id);
                campusFilter.appendChild(option);
            });

            if (campuses.length === 0) {
                var emptyOption = element('option', '', 'No campuses available');
                emptyOption.value = '';
                campusFilter.appendChild(emptyOption);
                campusFilter.disabled = true;
            } else {
                campusFilter.disabled = role !== 'nurse_head' || campuses.length <= 1;
            }

            await loadQueue(role);
        } catch (error) {
            campusFilter.replaceChildren(element('option', '', 'Could not load campuses'));
            campusFilter.disabled = true;
            reportError(error);
            await loadQueue(role);
        }
    }

    async function deleteConsultation(path, confirmationMessage) {
        if (!await window.uhConfirm(confirmationMessage, {
            title: 'Delete consultation request?',
            confirmText: 'Delete request',
            danger: true
        })) { return false; }

        try {
            await request(path, { method: 'DELETE' });
            notify('Consultation request deleted.', 'success');
            return true;
        } catch (error) {
            reportError(error);
            return false;
        }
    }

    function addMediaLink(parent, id, kind, label, prefix) {
        var link = element('a', 'uh-btn uh-btn-outline uh-btn-sm', label);
        link.href = '/api/clinic/' + prefix + '/consultations/' + encodeURIComponent(id) + '/media/' + kind;
        link.target = '_blank';
        link.rel = 'noopener';
        parent.appendChild(link);
    }

    async function openNurseConsultation(id, role) {
        activeNurseQueueRole = role || activeNurseQueueRole;
        activeNurseConsultationId = id;
        window.clearTimeout(nurseTranscriptionPollTimer);
        window.clearInterval(nurseTranscriptionTimerInterval);

        try {
            var prefix = activeNurseQueueRole === 'nurse_head' ? 'nurse-head' : 'nurse';
            var data = await request(prefix + '/consultations/' + encodeURIComponent(id));
            var record = data.consultation;
            var queue = document.getElementById('cw-nurse-queue');
            var queueHeader = document.getElementById('cw-nurse-queue-header');
            var queueFilters = document.getElementById('cw-queue-filters');
            var detail = document.getElementById('cw-nurse-detail');
            if (queue) { queue.hidden = true; }
            if (queueHeader) { queueHeader.hidden = true; }
            if (queueFilters) { queueFilters.hidden = true; }
            var queuePagination = document.getElementById('cw-queue-pagination');
            if (queuePagination) { queuePagination.hidden = true; }
            detail.hidden = false;
            detail.replaceChildren();

            var header = element('div', 'cw-detail-header');
            var backButton = element('button', 'uh-btn uh-btn-outline uh-btn-sm cw-back-button', 'Back to requests');
            backButton.type = 'button';
            backButton.addEventListener('click', function () { window.cwBackToQueue(activeNurseQueueRole); });
            var heading = element('div', 'cw-detail-heading');
            heading.appendChild(element('h2', '', (record.student && record.student.name ? record.student.name : 'Student') + (record.student && record.student.student_id ? ' · ' + record.student.student_id : '')));
            heading.appendChild(element('span', 'cw-badge', stageLabel(record.status)));
            heading.appendChild(priorityIndicator(record.triage_priority));
            header.append(backButton, heading);
            detail.appendChild(header);

            renderTranscriptionSummary(detail, record, id, prefix);
            renderNurseActions(detail, record);
            var isTranscriptionProcessing = record.status === 'audio_processing'
                || (record.transcription && record.transcription.status === 'processing');
            if (isTranscriptionProcessing || (record.ai_summary && record.ai_summary.status === 'pending')) {
                if (isTranscriptionProcessing) {
                    nurseTranscriptionTimerInterval = window.setInterval(function () {
                        var timer = detail.querySelector('.cw-transcription-timer');
                        if (timer) { updateTranscriptionTimer(timer); }
                    }, 1000);
                }
                scheduleNurseConsultationRefresh(id, detail);
            }
        } catch (error) {
            if (!handleMissingConsultation(error, activeNurseQueueRole)) { reportError(error); }
        }
    }

    function formatTranscriptionTime(seconds) {
        var totalSeconds = Math.max(0, Math.floor(seconds));
        var hours = Math.floor(totalSeconds / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var remainingSeconds = totalSeconds % 60;
        var clock = [minutes, remainingSeconds].map(function (part) {
            return String(part).padStart(2, '0');
        }).join(':');

        return hours ? hours + ':' + clock : clock;
    }

    function updateTranscriptionTimer(timer) {
        if (timer.dataset.started !== 'true') {
            timer.textContent = 'Waiting in queue · processing timer starts when a worker picks up the recording';

            return;
        }

        var elapsedAtRefresh = Number(timer.dataset.elapsedSeconds) || 0;
        var secondsSinceRefresh = Math.floor((Date.now() - Number(timer.dataset.updatedAt)) / 1000);
        var elapsed = elapsedAtRefresh + secondsSinceRefresh;
        var estimate = timer.dataset.totalSeconds;
        var sampleCount = Number(timer.dataset.sampleCount) || 0;

        if (estimate === '') {
            timer.textContent = 'Elapsed ' + formatTranscriptionTime(elapsed)
                + ' · Transcription processing · total runtime will be shown when complete';

            return;
        }

        timer.textContent = 'Elapsed ' + formatTranscriptionTime(elapsed)
            + ' · Estimated total time ' + formatTranscriptionTime(Number(estimate))
            + (sampleCount ? ' · based on ' + sampleCount + ' completed recording' + (sampleCount === 1 ? '' : 's') : '');
    }

    function scheduleNurseConsultationRefresh(id, detail) {
        nurseTranscriptionPollTimer = window.setTimeout(async function () {
            if (activeNurseConsultationId !== id || !detail.isConnected) { return; }

            try {
                var prefix = activeNurseQueueRole === 'nurse_head' ? 'nurse-head' : 'nurse';
                var data = await request(prefix + '/consultations/' + encodeURIComponent(id));
                var record = data.consultation;
                var isTranscriptionProcessing = record.status === 'audio_processing'
                    || (record.transcription && record.transcription.status === 'processing');
                var isAiSummaryPending = record.ai_summary && record.ai_summary.status === 'pending';
                if (isTranscriptionProcessing || isAiSummaryPending) {
                    var transcription = record.transcription;
                    var timer = detail.querySelector('.cw-transcription-timer');
                    if (timer && transcription && transcription.processing_started) {
                        timer.dataset.started = 'true';
                        timer.dataset.elapsedSeconds = String(transcription.processing_elapsed_seconds || 0);
                        timer.dataset.totalSeconds = transcription.estimated_total_seconds !== null
                            && transcription.estimated_total_seconds !== undefined
                            ? String(transcription.estimated_total_seconds)
                            : '';
                        timer.dataset.sampleCount = String(transcription.eta_sample_count || 0);
                        timer.dataset.updatedAt = String(Date.now());
                        updateTranscriptionTimer(timer);

                        var liveLabel = detail.querySelector('.cw-transcription-live-label');
                        if (liveLabel) {
                            liveLabel.textContent = 'Transcribing audio…';
                        }

                        var statusMessage = detail.querySelector('.cw-transcript-status');
                        if (statusMessage) {
                            statusMessage.textContent = 'The recording is being processed in the background by Groq. If it remains here for several minutes, check that the clinic queue worker is running.';
                        }
                    }

                    scheduleNurseConsultationRefresh(id, detail);
                    return;
                }

                await openNurseConsultation(id);
            } catch (error) {
                if (handleMissingConsultation(error, activeNurseQueueRole)) { return; }
                reportError(error);
                if (activeNurseConsultationId === id && detail.isConnected) {
                    scheduleNurseConsultationRefresh(id, detail);
                }
            }
        }, 5000);
    }

    function renderNurseActions(detail, record) {
        var isNurseHead = activeNurseQueueRole === 'nurse_head';
        var actions = element('div', 'cw-actions');
        addConsultationDecisionActions(actions, record, isNurseHead ? 'nurse_head' : 'nurse', function () {
            window.cwBackToQueue(activeNurseQueueRole);
        }, function () {
            return openNurseConsultation(record.id, activeNurseQueueRole);
        });
        detail.appendChild(actions);

        if (!record.appointment_id && !record.accepted_at
            && ['submitted', 'audio_processing', 'transcription_failed'].includes(record.status)) {
            addButton(actions, 'Accept request', function () {
                return acceptConsultationRequest(record);
            }, 'uh-btn uh-btn-primary uh-btn-sm cw-accept-button');
        }

        if (!isNurseHead && record.appointment_id && record.accepted_at && !record.nurse_id) {
            addButton(actions, 'Take for nurse review', async function () {
                try {
                    await request('nurse/consultations/' + record.id + '/accept', {
                        method: 'POST',
                        body: {}
                    });
                    notify('Consultation assigned to you for review.', 'success');
                    await openNurseConsultation(record.id);
                    await loadQueue('nurse');
                } catch (error) {
                    reportError(error);
                }
            }, 'uh-btn uh-btn-primary uh-btn-sm');
            return;
        }

        if (record.status === 'transcript_ready' && !record.appointment_id && !record.accepted_at) {
            renderNurseReview(detail, record);
        }

        if (record.status === 'nurse_review_complete') {
            renderNurseDisposition(detail, record);
        }
    }

    function renderNurseReview(parent, record) {
        var form = element('form', 'cw-form-grid');
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            await acceptConsultationRequest(record, form.elements.transcript.value);
        });

        var transcript = field(record.student_voice_path ? 'Full audio transcript' : 'Student concern', 'transcript', 'textarea');
        transcript.value = record.transcription && (record.transcription.ai_draft || record.transcription.raw_transcript) || record.concern || '';
        transcript.rows = 7;
        var transcriptDetails = element('details', 'cw-transcript-details cw-transcript-editor');
        transcriptDetails.appendChild(element('summary', '', 'View or correct the full transcript'));
        transcriptDetails.appendChild(transcript.parentNode);
        var clearTranscriptButton = element('button', 'uh-btn uh-btn-outline uh-btn-sm', 'Clear transcript editor');
        clearTranscriptButton.type = 'button';
        clearTranscriptButton.addEventListener('click', function () {
            transcript.value = '';
            transcript.focus();
        });
        transcriptDetails.appendChild(clearTranscriptButton);

        form.appendChild(transcriptDetails);
        var submit = element('button', 'uh-btn uh-btn-primary uh-btn-sm cw-accept-button', 'Accept consultation');
        submit.type = 'submit';
        form.appendChild(submit);
        parent.appendChild(form);
    }

    async function acceptConsultationRequest(record, transcript) {
        var role = activeNurseQueueRole;
        var prefix = role === 'nurse_head' ? 'nurse-head' : 'nurse';
        var payload = typeof transcript === 'string' ? { transcript: transcript } : {};
        try {
            await request(prefix + '/consultations/' + record.id + '/accept', {
                method: 'POST',
                body: payload
            });
            notify('Consultation accepted. Refer to a physician or dentist if specialist scheduling is needed.', 'success');
            await openAppointmentsAfterAcceptance(role);
        } catch (error) {
            reportError(error);
        }
    }

    function field(labelText, name, type) {
        var wrapper = element('div', 'cw-field cw-wide');
        var label = element('label', '', labelText);
        var input = type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
        input.name = name;
        if (type !== 'textarea') { input.type = type; }
        label.htmlFor = 'cw-' + name;
        input.id = label.htmlFor;
        wrapper.append(label, input);
        return input;
    }

    function renderNurseDisposition(parent, record) {
        var adviceForm = element('form', 'cw-form-grid');
        adviceForm.appendChild(element('h4', 'cw-wide', 'Close with nursing advice'));
        var advice = field('Treatment / student-facing nursing advice (required)', 'advice', 'textarea');
        advice.required = true;
        var emergency = field('Emergency instructions (optional)', 'emergency', 'textarea');
        adviceForm.append(advice.parentNode, emergency.parentNode);
        var adviceButton = element('button', 'uh-btn uh-btn-primary uh-btn-sm', 'Complete with advice');
        adviceButton.type = 'submit';
        adviceForm.appendChild(adviceButton);
        adviceForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            try {
                await request('nurse/consultations/' + record.id + '/advice', {
                    method: 'POST',
                    body: { nursing_advice: advice.value, emergency_instructions: emergency.value }
                });
                notify('Nursing advice sent to the student.', 'success');
                await openNurseConsultation(record.id);
                await loadQueue('nurse');
            } catch (error) { reportError(error); }
        });
        parent.appendChild(adviceForm);

        var referralForm = element('form', 'cw-form-grid');
        referralForm.appendChild(element('h4', 'cw-wide', 'Refer for physician or dentist review'));
        var role = field('Refer to', 'clinician_role', 'select');
        [['physician', 'Physician'], ['dentist', 'Dentist']].forEach(function (choice) {
            var option = element('option', '', choice[1]);
            option.value = choice[0];
            role.appendChild(option);
        });
        var urgency = field('Urgency', 'urgency', 'select');
        [['routine', 'Routine'], ['urgent', 'Urgent'], ['emergency', 'Emergency']].forEach(function (choice) {
            var option = element('option', '', choice[1]);
            option.value = choice[0];
            urgency.appendChild(option);
        });
        var slot = field('Published date and time', 'availability_slot', 'select');
        slot.required = true;
        var reason = field('Reason for referral', 'reason', 'textarea');
        referralForm.append(role.parentNode, slot.parentNode, urgency.parentNode, reason.parentNode);
        async function loadReferralSlots() {
            slot.replaceChildren();
            var placeholder = element('option', '', 'Loading available times…');
            placeholder.value = '';
            slot.appendChild(placeholder);
            slot.disabled = true;
            try {
                var query = new URLSearchParams();
                if (activeNurseQueueRole === 'nurse_head') {
                    query.set('campus_id', record.campus_id);
                }
                var suffix = query.toString() ? '?' + query.toString() : '';
                var response = await fetch('/api/clinic/availability' + suffix, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                });
                var data = await response.json().catch(function () { return {}; });
                if (!response.ok || !data.success) {
                    var validation = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(data.message || data.error || validation || 'Could not load published appointment times.');
                }
                slot.replaceChildren();
                var chooseOption = element('option', '', 'Choose an available date and time');
                chooseOption.value = '';
                slot.appendChild(chooseOption);
                var currentTime = new Date();
                var currentLocalTime = String(currentTime.getHours()).padStart(2, '0')
                    + ':' + String(currentTime.getMinutes()).padStart(2, '0');
                (data.availability || [])
                    .filter(function (schedule) {
                        return schedule.provider_type === role.value
                            && Number(schedule.remaining_capacity) > 0;
                    })
                    .forEach(function (schedule) {
                        (schedule.available_times || [])
                            .filter(function (time) {
                                return !(schedule.booked_times || []).includes(time)
                                    && (schedule.available_date !== nurseLocalDateString() || time > currentLocalTime);
                            })
                            .forEach(function (time) {
                                var option = element(
                                    'option',
                                    '',
                                    schedule.available_date + ' · ' + time + ' · ' + schedule.remaining_capacity + ' spots left'
                                );
                                option.value = schedule.id + '|' + time;
                                slot.appendChild(option);
                            });
                    });
                if (slot.options.length === 1) {
                    slot.options[0].textContent = 'No published times with capacity for this clinician';
                }
            } catch (error) {
                slot.replaceChildren();
                var errorOption = element('option', '', error.message || 'Could not load available times');
                errorOption.value = '';
                slot.appendChild(errorOption);
                reportError(error);
            } finally {
                slot.disabled = false;
            }
        }
        role.addEventListener('change', loadReferralSlots);
        loadReferralSlots();
        var referButton = element('button', 'uh-btn uh-btn-outline uh-btn-sm', 'Refer to clinician');
        referButton.type = 'submit';
        referralForm.appendChild(referButton);
        referralForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            var selectedSlot = slot.value.split('|');
            if (selectedSlot.length !== 2 || !selectedSlot[0] || !selectedSlot[1]) {
                notify('Choose a published date and time before referring.', 'error');
                return;
            }
            try {
                var consultationPrefix = activeNurseQueueRole === 'nurse_head' ? 'nurse-head' : 'nurse';
                await request(consultationPrefix + '/consultations/' + record.id + '/refer', {
                    method: 'POST',
                    body: {
                        clinician_role: role.value,
                        availability_id: Number(selectedSlot[0]),
                        appt_time: selectedSlot[1],
                        urgency: urgency.value,
                        reason: reason.value
                    }
                });
                notify('The consultation was scheduled for clinician review at ' + slot.options[slot.selectedIndex].textContent + '.', 'success');
                await openNurseConsultation(record.id);
                await loadQueue('nurse');
            } catch (error) { reportError(error); }
        });
        parent.appendChild(referralForm);
    }

    async function openPhysicianConsultation(id) {
        try {
            var data = await request('physician/consultations/' + encodeURIComponent(id));
            var record = data.consultation;
            document.getElementById('cw-physician-queue').hidden = true;
            var detail = document.getElementById('cw-physician-detail');
            detail.hidden = false;
            detail.replaceChildren();
            detail.appendChild(element('h3', '', (record.student && record.student.name ? record.student.name : 'Student') + ' · ' + (record.student && record.student.student_id ? record.student.student_id : '')));
            detail.appendChild(element('span', 'cw-badge', stageLabel(record.status)));
            addDetail(detail, 'Main concern', record.concern);
            addDetail(detail, 'Nurse summary', record.nurse_summary);
            var vitals = record.vitals || {};
            var formatVital = function (value, suffix) {
                return value === null || value === undefined || value === '' ? null : value + suffix;
            };
            var vitalSummary = [
                ['BP', vitals.bp],
                ['Temp', formatVital(vitals.temp, ' °C')],
                ['Pulse', formatVital(vitals.pulse, ' bpm')],
                ['RR', formatVital(vitals.rr, '/min')],
                ['SpO₂', formatVital(vitals.o2_sat, '%')]
            ].filter(function (item) { return item[1] !== null && item[1] !== undefined && item[1] !== ''; })
                .map(function (item) { return item[0] + ': ' + item[1]; })
                .join(' · ');
            if (vitalSummary) { addDetail(detail, 'Vitals recorded', vitalSummary); }
            var transcriptText = record.transcription
                ? (record.transcription.nurse_corrected_transcript || record.transcription.raw_transcript)
                : '';
            if (transcriptText) {
                var transcriptDetails = element('details', 'cw-transcript-details');
                transcriptDetails.appendChild(element('summary', '', 'View full transcript for verification'));
                transcriptDetails.appendChild(element('div', 'cw-transcript-text', transcriptText));
                detail.appendChild(transcriptDetails);
            }
            if (record.media && record.media.official_audio) {
                var audioLink = element('a', 'uh-btn uh-btn-outline uh-btn-sm', 'Play official consultation recording');
                audioLink.href = record.media.official_audio;
                audioLink.target = '_blank';
                audioLink.rel = 'noopener';
                detail.appendChild(audioLink);
            }

            var form = element('form', 'cw-form-grid');
            var diagnosis = field('Diagnosis', 'diagnosis', 'textarea');
            var treatment = field('Treatment plan', 'treatment_plan', 'textarea');
            var instructions = field('Student-facing instructions', 'approved_instructions', 'textarea');
            var followUp = field('Follow-up plan', 'follow_up', 'textarea');
            treatment.required = true;
            diagnosis.value = record.diagnosis || '';
            treatment.value = record.treatment_plan || '';
            instructions.value = record.approved_instructions || '';
            followUp.value = record.follow_up || '';
            form.append(diagnosis.parentNode, treatment.parentNode, instructions.parentNode, followUp.parentNode);
            var decision = field('Decision', 'decision', 'select');
            [
                ['approve', 'Approve and send instructions'],
                ['request_information', 'Request more information'],
                ['in_person_followup', 'Require in-person follow-up']
            ].forEach(function (choice) {
                var option = element('option', '', choice[1]);
                option.value = choice[0];
                decision.appendChild(option);
            });
            form.appendChild(decision.parentNode);
            var updateTreatmentRequirement = function () {
                treatment.required = decision.value === 'approve';
            };
            decision.addEventListener('change', updateTreatmentRequirement);
            updateTreatmentRequirement();
            var submit = element('button', 'uh-btn uh-btn-primary uh-btn-sm', 'Save clinician decision');
            submit.type = 'submit';
            form.appendChild(submit);
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                try {
                    await request('physician/consultations/' + record.id + '/review', {
                        method: 'POST',
                        body: {
                            decision: decision.value,
                            diagnosis: diagnosis.value,
                            treatment_plan: treatment.value,
                            approved_instructions: instructions.value,
                            follow_up: followUp.value
                        }
                    });
                    if (decision.value === 'approve' && typeof window.phLoadAcceptedDiagnosisPatients === 'function') {
                        window.phLoadAcceptedDiagnosisPatients();
                    }
                    notify('Clinician decision saved.', 'success');
                    await openPhysicianConsultation(record.id);
                    await loadQueue('physician');
                } catch (error) { reportError(error); }
            });
            detail.appendChild(form);
        } catch (error) {
            if (!handleMissingConsultation(error, 'physician')) { reportError(error); }
        }
    }

    function openPage(pageId, role) {
        var nav = document.querySelector('.uh-nav-item[data-title="' + (role === 'student' ? 'Request Consultation' : 'Consultation Requests') + '"]');
        if (typeof window.uhShowPage === 'function') { window.uhShowPage(pageId, nav); }
        if (role === 'student') { loadStudentConsultations(); }
        else if (role === 'nurse' || role === 'nurse_head') { loadConsultationCampuses(role); }
        else { loadQueue(role); }
    }

    window.cwOpenConsultations = function () {
        var role = window.__clinicUser && window.__clinicUser.role || document.body.dataset.clinicRole;
        if (!role) {
            var body = document.body;
            role = body.classList.contains('student-dashboard')
                ? 'student'
                : body.classList.contains('physician-dashboard')
                    ? (window.__clinicUser && window.__clinicUser.role === 'dentist' ? 'dentist' : 'physician')
                    : 'nurse';
        }
        if (role === 'student') { openPage('consultations', role); return; }
        if (role === 'nurse' || role === 'nurse_head') {
            openPage('consultations', role);
            return;
        }
        if (role === 'physician' || role === 'dentist') { openPage('consultations', 'physician'); }
    };

    window.cwBackToQueue = function (role) {
        var isNurseQueue = role === 'nurse' || role === 'nurse_head';
        var detailId = isNurseQueue ? 'cw-nurse-detail' : 'cw-physician-detail';
        var queueId = isNurseQueue ? 'cw-nurse-queue' : 'cw-physician-queue';
        var detail = document.getElementById(detailId);
        var queue = document.getElementById(queueId);
        if (isNurseQueue) {
            activeNurseConsultationId = null;
            window.clearTimeout(nurseTranscriptionPollTimer);
            window.clearInterval(nurseTranscriptionTimerInterval);
        }
        if (detail) { detail.hidden = true; }
        if (queue) { queue.hidden = false; }
        if (isNurseQueue) {
            var queueHeader = document.getElementById('cw-nurse-queue-header');
            var queueFilters = document.getElementById('cw-queue-filters');
            if (queueHeader) { queueHeader.hidden = false; }
            if (queueFilters) { queueFilters.hidden = false; }
            var queuePagination = document.getElementById('cw-queue-pagination');
            if (queuePagination) { queuePagination.hidden = false; }
        }
        loadQueue(role);
    };

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('cw-student-form');
        if (form) {
            form.addEventListener('submit', submitStudentRequest);
            form.addEventListener('reset', resetStudentRecording);
        }
        var studentConcern = document.getElementById('cw-student-concern');
        if (studentConcern) {
            studentConcern.addEventListener('invalid', function () {
                if (!studentConcern.value.trim()) {
                    studentConcern.setCustomValidity('Please enter a short description of your consultation.');
                }
            });
            studentConcern.addEventListener('input', function () {
                studentConcern.setCustomValidity('');
            });
        }
        var recordStart = document.getElementById('cw-record-start');
        if (recordStart) { recordStart.addEventListener('click', startStudentRecording); }
        var recordStop = document.getElementById('cw-record-stop');
        if (recordStop) { recordStop.addEventListener('click', stopStudentRecording); }
        var recordRetake = document.getElementById('cw-record-retake');
        if (recordRetake) { recordRetake.addEventListener('click', startStudentRecording); }
        var recordClear = document.getElementById('cw-record-clear');
        if (recordClear) { recordClear.addEventListener('click', resetStudentRecording); }
        window.addEventListener('beforeunload', function () {
            stopStudentMicrophone();
            if (studentRecordingUrl) { URL.revokeObjectURL(studentRecordingUrl); }
        });
        var refresh = document.getElementById('cw-refresh-queue');
        if (refresh) {
            refresh.addEventListener('click', function () {
                var role = window.__clinicUser && window.__clinicUser.role || document.body.dataset.clinicRole;
                if (!['nurse', 'nurse_head'].includes(role)) { role = 'physician'; }
                loadQueue(role);
            });
        }
        var campusFilter = document.getElementById('cw-queue-campus');
        if (campusFilter) {
            campusFilter.addEventListener('change', function () {
                var role = window.__clinicUser && window.__clinicUser.role || document.body.dataset.clinicRole || 'nurse';
                loadQueue(role, 1);
            });
        }
        var priorityFilter = document.getElementById('cw-queue-priority');
        if (priorityFilter) {
            priorityFilter.addEventListener('change', function () {
                var role = window.__clinicUser && window.__clinicUser.role || document.body.dataset.clinicRole || 'nurse';
                loadQueue(role, 1);
            });
        }
        var studentSearch = document.getElementById('cw-queue-student-search');
        if (studentSearch) {
            studentSearch.addEventListener('input', function () {
                window.clearTimeout(nurseQueueSearchTimer);
                nurseQueueSearchTimer = window.setTimeout(function () {
                    var role = window.__clinicUser && window.__clinicUser.role || document.body.dataset.clinicRole || 'nurse';
                    loadQueue(role, 1);
                }, 250);
            });
        }
        loadStudentConsultations();
        var activeRole = window.__clinicUser && window.__clinicUser.role || document.body.dataset.clinicRole;
        if (activeRole === 'nurse' || activeRole === 'nurse_head') {
            activeNurseQueueRole = activeRole;
            loadConsultationCampuses(activeRole);
        }
    });
}());
