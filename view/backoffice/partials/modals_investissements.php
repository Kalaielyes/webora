<?php // Modals for investissements tab ?>
<!-- Meeting Modal -->
<div id="meeting-modal-overlay" class="meeting-modal-overlay" aria-hidden="true">
  <div class="meeting-modal">
    <div class="meeting-modal-head">
      <div class="meeting-modal-title">Planifier une reunion video</div>
      <button type="button" class="act-btn" id="meeting-close-btn">×</button>
    </div>
    <form id="meeting-form">
      <div class="meeting-grid">
        <div class="meeting-row"><label for="meeting-investor-email">Email investisseur</label><input id="meeting-investor-email" name="invited_emails" type="email" required /></div>
        <div class="meeting-row"><label for="meeting-date">Date</label><input id="meeting-date" name="date" type="date" required /></div>
        <div class="meeting-row"><label for="meeting-time">Heure</label><input id="meeting-time" name="time" type="time" required /></div>
        <div class="meeting-row"><label for="meeting-message">Message (optionnel)</label><textarea id="meeting-message" name="message" rows="3" placeholder="Details du rendez-vous"></textarea></div>
      </div>
      <div class="meeting-actions">
        <button type="button" class="filter-btn" id="meeting-cancel-btn">Annuler</button>
        <button type="submit" class="btn-primary">Envoyer l'invitation</button>
      </div>
      <div id="meeting-error" class="meeting-msg error"></div>
      <div id="meeting-success" class="meeting-msg success"></div>
    </form>
  </div>
</div>

<!-- Score Modal -->
<div id="score-modal" class="meeting-modal-overlay" style="z-index:16000">
  <div class="meeting-modal" style="width:min(620px,100%)">
    <div class="meeting-modal-head">
      <div class="meeting-modal-title" id="score-modal-title">Détails du score</div>
      <button type="button" class="act-btn" id="score-modal-close">×</button>
    </div>
    <div style="padding:1rem;max-height:70vh;overflow:auto;">
      <div id="score-modal-summary" style="font-size:.78rem;color:var(--muted);margin-bottom:.8rem;"></div>
      <div id="score-modal-factors"></div>
    </div>
  </div>
</div>
