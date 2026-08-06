<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EptOnlineTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new EptOnlineTemplateSheetExport('META', [
                ['key', 'value'],
                ['title', 'Paket EPT Online 2026 Gelombang 1'],
                ['listening_duration_minutes', '35'],
                ['structure_duration_minutes', '25'],
                ['reading_duration_minutes', '55'],
                ['listening_title', 'Listening Comprehension'],
                ['structure_title', 'Structure and Written Expression'],
                ['reading_title', 'Reading Comprehension'],
                ['listening_instructions', 'Putar audio listening satu kali dan kerjakan soal sesuai urutan.'],
                ['listening_intro_heading', 'Petunjuk Listening'],
                ['listening_intro_text', "In this section of the test, you will have an opportunity to demonstrate your ability to understand conversations and talks in English.\nThere are three parts to this section with special directions for each part.\nAnswer all the questions on the basis of what is stated or implied by the speakers you hear.\nDo not take notes or write in your test book at any time."],
                ['listening_part_a_instruction', 'Directions: In Part A you will hear short conversations between two people. After each conversation, you will hear a question. The conversations and questions will not be repeated. Then choose the best answer.'],
                ['listening_part_a_example_1_title', 'Listen to an example.'],
                ['listening_part_a_example_1_audio_label', 'On the recording, you will hear:'],
                ['listening_part_a_example_1_audio_text', "(man) That exam was just awful.\n(woman) Oh, it could have been worse.\n(narrator) What does the woman mean?"],
                ['listening_part_a_example_1_book_label', 'In your test book, you will read:'],
                ['listening_part_a_example_1_book_text', "(A) The exam was really awful.\n(B) It was the worst exam she had ever seen.\n(C) It couldn't have been more difficult.\n(D) It wasn't that hard."],
                ['listening_part_a_example_1_explanation', 'You learn from the conversation that the man thought the exam was very difficult and that the woman disagreed with the man. Therefore, the correct choice is (D).'],
                ['listening_part_b_instruction', 'Directions: In Part B you will hear longer conversations. After each conversation, you will hear several questions. The conversations and questions will not be repeated. Then choose the best answer.'],
                ['listening_part_b_example_1_title', ''],
                ['listening_part_b_example_1_audio_label', 'On the recording, you will hear:'],
                ['listening_part_b_example_1_audio_text', ''],
                ['listening_part_b_example_1_book_label', 'In your test book, you will read:'],
                ['listening_part_b_example_1_book_text', ''],
                ['listening_part_b_example_1_explanation', ''],
                ['listening_part_c_instruction', 'Directions: In Part C you will hear several talks. After each talk, you will hear several questions. The talks and questions will not be repeated. Then choose the best answer.'],
                ['listening_part_c_example_1_title', 'Here is an example.'],
                ['listening_part_c_example_1_audio_label', 'On the recording, you will hear:'],
                ['listening_part_c_example_1_audio_text', ''],
                ['listening_part_c_example_1_book_label', 'In your test book, you will read:'],
                ['listening_part_c_example_1_book_text', ''],
                ['listening_part_c_example_1_explanation', ''],
                ['listening_part_c_example_2_title', 'Now listen to another sample question.'],
                ['listening_part_c_example_2_audio_label', 'On the recording, you will hear:'],
                ['listening_part_c_example_2_audio_text', ''],
                ['listening_part_c_example_2_book_label', 'In your test book, you will read:'],
                ['listening_part_c_example_2_book_text', ''],
                ['listening_part_c_example_2_explanation', ''],
                ['structure_instructions', 'Pilih jawaban terbaik untuk setiap soal structure.'],
                ['structure_part_a_instruction', 'Directions: Question 1-15 are incomplete sentences. Beneath each sentence you will see four words or phrases, marked (A), (B), (C), and (D). Choose the one word or phrase that best completes the sentence.'],
                ['structure_part_b_instruction', 'Directions: In questions 16-40, each sentence has four underlined words or phrases. The four underlined parts of the sentence are marked (A), (B), (C), and (D). Identify the one underlined word or phrase that must be changed in order for the sentence to be correct.'],
                ['reading_instructions', 'Baca passage lalu jawab soal reading yang terkait.'],
            ]),
            new EptOnlineTemplateSheetExport('LISTENING', [
                ['question_no', 'part', 'passage_code', 'group_code', 'instruction', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option'],
                ['1', 'A', '', 'L-A-01', '', '', 'He is leaving now.', 'He will stay longer.', 'He needs a ticket.', 'He missed the train.', 'B'],
            ]),
            new EptOnlineTemplateSheetExport('STRUCTURE', [
                ['question_no', 'part', 'passage_code', 'group_code', 'instruction', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option'],
                ['1', 'A', '', 'S-A-01', 'Choose the correct structure.', 'The students ____ in the library when the lecturer arrived.', 'study', 'were studying', 'has studied', 'studies', 'B'],
            ]),
            new EptOnlineTemplateSheetExport('READING_PASSAGES', [
                ['passage_code', 'title', 'passage_text'],
                ['R-01', 'Sample Passage', 'This is a sample reading passage. Replace this text with the actual passage used for the reading section.'],
            ]),
            new EptOnlineTemplateSheetExport('READING_QUESTIONS', [
                ['question_no', 'part', 'passage_code', 'group_code', 'instruction', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option'],
                ['1', 'A', 'R-01', 'R-A-01', 'Choose the best answer based on the passage.', 'What is the main idea of the passage?', 'A historical event', 'A travel guide', 'A sample reading text', 'A scientific formula', 'C'],
            ]),
        ];
    }
}
