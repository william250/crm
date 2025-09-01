// CRM Pipeline Management
class PipelineManager {
    constructor() {
        this.stages = {
            'new': { name: 'New', color: 'primary' },
            'contacted': { name: 'Contacted', color: 'info' },
            'qualified': { name: 'Qualified', color: 'warning' },
            'proposal': { name: 'Proposal', color: 'secondary' },
            'won': { name: 'Won', color: 'success' },
            'lost': { name: 'Lost', color: 'danger' }
        };
        this.deals = [];
        this.init();
    }

    init() {
        this.loadPipeline();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Add Deal Modal
        const addDealForm = document.getElementById('addDealForm');
        if (addDealForm) {
            addDealForm.addEventListener('submit', (e) => this.handleAddDeal(e));
        }

        // Edit Deal Modal
        const editDealForm = document.getElementById('editDealForm');
        if (editDealForm) {
            editDealForm.addEventListener('submit', (e) => this.handleEditDeal(e));
        }

        // Delete Deal Button
        const deleteDealBtn = document.getElementById('deleteDealBtn');
        if (deleteDealBtn) {
            deleteDealBtn.addEventListener('click', () => this.handleDeleteDeal());
        }

        // Deal card clicks
        document.addEventListener('click', (e) => {
            const dealCard = e.target.closest('.deal-card');
            if (dealCard) {
                const dealId = dealCard.getAttribute('data-deal-id');
                this.openEditModal(dealId);
            }
        });
    }

    async loadPipeline() {
        try {
            const response = await axios.get('/api/crm/pipeline');
            
            // Extract deals from API response
            this.deals = [];
            
            if (response.data && response.data.success && response.data.data && Array.isArray(response.data.data.leads)) {
                this.deals = response.data.data.leads;
            }
            
            this.renderPipeline();
            this.updateStatistics(response.data);
        } catch (error) {
            console.error('Error loading pipeline:', error);
            this.deals = [];
            this.showError('Failed to load pipeline data');
        }
    }

    renderPipeline() {
        const container = document.getElementById('pipelineContainer');
        if (!container) return;

        container.innerHTML = '';

        Object.keys(this.stages).forEach(stageKey => {
            const stage = this.stages[stageKey];
            const stageDeals = this.deals.filter(deal => deal.status === stageKey);
            
            const stageColumn = this.createStageColumn(stageKey, stage, stageDeals);
            container.appendChild(stageColumn);
        });
    }

    createStageColumn(stageKey, stage, deals) {
        const col = document.createElement('div');
        col.className = 'col-md-2 mb-4';
        
        col.innerHTML = `
            <div class="card h-100">
                <div class="card-header bg-${stage.color} text-white">
                    <h6 class="mb-0">${stage.name}</h6>
                    <small>${deals.length} deals</small>
                </div>
                <div class="card-body p-2" style="min-height: 400px; max-height: 400px; overflow-y: auto;">
                    <div class="deals-container">
                        ${deals.map(deal => this.renderDealCard(deal)).join('')}
                    </div>
                </div>
            </div>
        `;
        
        return col;
    }

    renderDealCard(deal) {
        const value = deal.value ? `$${parseFloat(deal.value).toLocaleString()}` : 'No value';
        const assignedUser = deal.assigned_user_name || 'Unassigned';
        
        return `
            <div class="card mb-2 deal-card" data-deal-id="${deal.id}" style="cursor: pointer;">
                <div class="card-body p-2">
                    <h6 class="card-title mb-1" style="font-size: 0.9rem;">${deal.name || 'No name'}</h6>
                    <p class="card-text mb-1" style="font-size: 0.8rem;">
                        <strong>Email:</strong> ${deal.email || 'No email'}<br>
                        <strong>Phone:</strong> ${deal.phone || 'No phone'}<br>
                        <strong>Source:</strong> ${deal.source || 'No source'}
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">${value}</small>
                        <small class="text-muted">${assignedUser}</small>
                    </div>
                </div>
            </div>
        `;
    }

    updateStatistics(data) {
        const stats = data.statistics || {};
        
        // Update total deals
        const totalElement = document.getElementById('totalDeals');
        if (totalElement) {
            totalElement.textContent = this.deals.length;
        }

        // Update total value
        const totalValue = this.deals.reduce((sum, deal) => sum + (parseFloat(deal.value) || 0), 0);
        const valueElement = document.getElementById('totalValue');
        if (valueElement) {
            valueElement.textContent = `$${totalValue.toLocaleString()}`;
        }

        // Update conversion rate
        const wonDeals = this.deals.filter(deal => deal.status === 'won').length;
        const conversionRateValue = this.deals.length > 0 ? ((wonDeals / this.deals.length) * 100).toFixed(1) : 0;
        const conversionRate = document.getElementById('conversionRate');
        if (conversionRate) {
            conversionRate.textContent = `${conversionRateValue}%`;
        }

        // Update average deal size
        const avgDealSize = this.deals.length > 0 ? (totalValue / this.deals.length).toFixed(0) : 0;
        const avgElement = document.getElementById('avgDealSize');
        if (avgElement) {
            avgElement.textContent = `$${parseFloat(avgDealSize).toLocaleString()}`;
        }
    }

    async handleAddDeal(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const dealData = {
            name: formData.get('title') || formData.get('name'),
            email: formData.get('contact') || formData.get('email'),
            phone: formData.get('phone') || '',
            source: formData.get('source') || 'website',
            value: formData.get('value'),
            status: formData.get('stage') || formData.get('status') || 'new'
        };

        try {
            await axios.post('/api/crm/leads', dealData);
            bootstrap.Modal.getInstance(document.getElementById('addDealModal')).hide();
            e.target.reset();
            this.loadPipeline();
            this.showSuccess('Deal added successfully');
        } catch (error) {
            console.error('Error adding deal:', error);
            this.showError('Failed to add deal');
        }
    }

    async handleEditDeal(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const dealId = formData.get('deal_id');
        const dealData = {
            name: formData.get('title') || formData.get('name'),
            email: formData.get('contact') || formData.get('email'),
            phone: formData.get('phone') || '',
            source: formData.get('source') || 'website',
            value: formData.get('value'),
            status: formData.get('stage') || formData.get('status')
        };

        try {
            await axios.put(`/api/crm/leads/${dealId}`, dealData);
            bootstrap.Modal.getInstance(document.getElementById('editDealModal')).hide();
            this.loadPipeline();
            this.showSuccess('Deal updated successfully');
        } catch (error) {
            console.error('Error updating deal:', error);
            this.showError('Failed to update deal');
        }
    }

    openEditModal(dealId) {
        const deal = this.deals.find(d => d.id == dealId);
        if (!deal) return;

        // Populate edit form
        document.getElementById('editDealId').value = deal.id;
        document.getElementById('editDealTitle').value = deal.name || '';
        document.getElementById('editDealValue').value = deal.value || '';
        document.getElementById('editDealContact').value = deal.email || '';
        document.getElementById('editDealStage').value = deal.status || '';
        document.getElementById('editDealNotes').value = deal.notes || '';

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editDealModal'));
        modal.show();
    }

    async handleDeleteDeal() {
        const dealId = document.getElementById('editDealId').value;
        if (!dealId) return;

        if (!confirm('Are you sure you want to delete this deal?')) {
            return;
        }

        try {
            await axios.delete(`/api/crm/leads/${dealId}`);
            bootstrap.Modal.getInstance(document.getElementById('editDealModal')).hide();
            this.loadPipeline();
            this.showSuccess('Deal deleted successfully');
        } catch (error) {
            console.error('Error deleting deal:', error);
            this.showError('Failed to delete deal');
        }
    }

    showSuccess(message) {
        // You can implement a toast notification here
        console.log('Success:', message);
    }

    showError(message) {
        // You can implement a toast notification here
        console.error('Error:', message);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new PipelineManager();
});