document.addEventListener('DOMContentLoaded', () => {
	const list = document.getElementById('participants');
	const add = document.getElementById('addParticipant');

	if (list && add) {
		add.addEventListener('click', () => {
			const count = list.querySelectorAll('input').length;

			if (count >= 8) {
				add.disabled = true;
				return;
			}

			const row = document.createElement('div');
			row.className = 'participant-input mb-2 d-flex gap-2';

			const input = document.createElement('input');
			input.className = 'form-control';
			input.name = 'participants[]';
			input.required = true;
			input.placeholder = `Имя участника ${count + 1}`;

			const remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'btn btn-outline-danger';
			remove.textContent = '×';

			remove.addEventListener('click', () => {
				row.remove();
				add.disabled = list.querySelectorAll('input').length >= 8;
			});

			row.append(input, remove);
			list.append(row);
			add.disabled = list.querySelectorAll('input').length >= 8;
		});
	}
});
