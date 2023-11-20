const { Octokit } = require("@octokit/core");
const octokit = new Octokit({ auth: process.env.GITHUB_TOKEN });

async function run() {
  const issueContext = JSON.parse(process.env.GITHUB_CONTEXT);
  const issueID = issueContext.issue.number;
  const issueTitle = issueContext.issue.title;
  const repoName = issueContext.repository.name;
  const repoOwner = issueContext.repository.owner.login;

  try {
    switch (issueContext.action) {
      case 'opened':
        const body = issueContext.issue.body +
          "\n\n<br /><hr>\nCare about this issue? Want to get it " +
          "resolved sooner? If you are a " +
          "<a href='https://www.mautic.org/become-a-member-of-mautic'>member " +
          "of Mautic</a>, you can add some funds to the " +
          "<a href='https://opencollective.com/mautic/projects/bounties'>Bounties Project</a> " +
          "so that the person who completes this task can claim those funds once it is " +
          "merged by a member of the core team!";
        
        await octokit.request('PATCH /repos/{owner}/{repo}/issues/{issue_number}', {
          owner: repoOwner,
          repo: repoName,
          issue_number: issueID,
          body: body,
        });
        break;
    
      case 'labeled':
        const labelName = issueContext.label.name;

        if (labelName === 'bounty') {
          const comment =
            "This issue has a bounty associated with it. Check the total available " +
            "<a href='https://opencollective.com/mautic/projects/bounties/transactions?searchTerm=%23" +
            issueID + "'>here</a>.";
      
          await octokit.request('POST /repos/{owner}/{repo}/issues/{issue_number}/comments', {
            owner: repoOwner,
            repo: repoName,
            issue_number: issueID,
            body: comment
          });
        }
        break;
  
      default:
        break;
    }
  } catch (error) {
    console.error(error);
  }
}

run();
