/**
 * Manages the student contact panel interactions:
 * - toggling the messages panel visibility
 * - toggling the reply form for individual messages
 */
class ContactStudentManager {
    /**
     * Toggles the visibility of the messages panel.
     * Updates the arrow icon accordingly.
     */
    toggleMessages() {
        const panel = document.getElementById('messagesPanel');
        const arrow = document.getElementById('msgArrow');
        if (!panel) return;

        // Determine current state
        const isOpen = panel.style.display !== 'none';

        // Toggle display
        panel.style.display = isOpen ? 'none' : 'block';

        // Update arrow icon
        if (arrow) arrow.textContent = isOpen ? '▼' : '▲';
    }

    /**
     * Toggles the visibility of a reply form for a specific message.
     * @param {string|number} id The unique ID of the message
     */
    toggleReplyForm(id) {
        const form = document.getElementById('replyForm' + id);
        if (!form) return;

        // Toggle display
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

// Create a singleton instance
const ContactStudent = new ContactStudentManager();

// Expose functions to global scope for inline HTML onclick handlers
window.toggleMessages = () => ContactStudent.toggleMessages();
window.toggleReplyForm = (id) => ContactStudent.toggleReplyForm(id);